<?php
// payfast.php sends donors to PayFast to pay and checks the payment notifications PayFast sends back
// 1 campaign.php saves the donation as pending and sends a signed form to PayFast
// 2 the donor pays or cancels on PayFast then comes back to campaign.php
// 3 PayFast sends the result to payfast-notify.php, only that marks a donation complete since anyone can open the return page
// the settings come from config/.env locally or environment variables on Render
// PAYFAST_SANDBOX is true while testing and false sends real payments
// PAYFAST_SITE_URL is optional, like an ngrok address so PayFast can reach a local copy of the site

require_once __DIR__ . '/../../config/env.php';

// payfast merchant details and whether we are in sandbox mode
function payfastConfig(): array
{
    $sandbox = filter_var(getenv('PAYFAST_SANDBOX') ?: 'true', FILTER_VALIDATE_BOOLEAN);

    return [
        'merchantID'  => (string) getenv('PAYFAST_MERCHANT_ID'),
        'merchantKey' => (string) getenv('PAYFAST_MERCHANT_KEY'),
        'passphrase'  => (string) getenv('PAYFAST_PASSPHRASE'),
        'sandbox'     => $sandbox,
        'host'        => $sandbox ? 'sandbox.payfast.co.za' : 'www.payfast.co.za',
    ];
}

// false when the merchant details are not set so the site can say donations are unavailable
function payfastConfigured(): bool
{
    $config = payfastConfig();
    return $config['merchantID'] !== '' && $config['merchantKey'] !== '';
}

// address the donation form is sent to
function payfastProcessURL(): string
{
    return 'https://' . payfastConfig()['host'] . '/eng/process';
}

// the site's address, on Render the proxy header says whether the visitor used https
function siteBaseURL(): string
{
    $override = rtrim((string) getenv('PAYFAST_SITE_URL'), '/');
    if ($override !== '') {
        return $override;
    }

    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $folder = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $folder;
}

// payfast signature, every non empty field in order plus the passphrase then hashed
// the order matters so build $fields in the order PayFast documents
function payfastSignature(array $fields, string $passphrase): string
{
    $pairs = [];
    foreach ($fields as $key => $value) {
        $value = trim((string) $value);
        if ($value !== '') {
            $pairs[] = $key . '=' . urlencode($value);
        }
    }

    $query = implode('&', $pairs);
    if ($passphrase !== '') {
        $query .= '&passphrase=' . urlencode(trim($passphrase));
    }

    return md5($query);
}

// hidden fields for the form that sends a donor to PayFast
function payfastCheckoutFields(array $donation): array
{
    $config = payfastConfig();
    $base = siteBaseURL();
    $reference = urlencode($donation['reference']);

    $fields = [
        'merchant_id'      => $config['merchantID'],
        'merchant_key'     => $config['merchantKey'],
        'return_url'       => $base . '/campaign.php?donation=return&ref=' . $reference,
        'cancel_url'       => $base . '/campaign.php?donation=cancelled&ref=' . $reference,
        'notify_url'       => $base . '/payfast-notify.php',
        'name_first'       => mb_substr($donation['firstName'], 0, 100),
        'name_last'        => mb_substr($donation['lastName'], 0, 100),
        'email_address'    => mb_substr($donation['email'], 0, 100),
        'm_payment_id'     => $donation['reference'],
        'amount'           => number_format((float) $donation['amount'], 2, '.', ''),
        'item_name'        => mb_substr('Donation: ' . $donation['campaignName'], 0, 100),
        'custom_int1'      => (string) (int) $donation['campaignID'],
    ];

    $fields['signature'] = payfastSignature($fields, $config['passphrase']);

    return $fields;
}

// check a payment notification the four ways PayFast asks
// returns null when valid or the reason it was rejected for the error log
function payfastValidateNotification(array $post, float $expectedAmount): ?string
{
    $config = payfastConfig();

    // 1 the signature matches, using every field before the signature in the order received
    $pairs = [];
    foreach ($post as $key => $value) {
        if ($key === 'signature') {
            break;
        }
        $pairs[] = $key . '=' . urlencode((string) $value);
    }
    $paramString = implode('&', $pairs);

    $toSign = $paramString;
    if ($config['passphrase'] !== '') {
        $toSign .= '&passphrase=' . urlencode(trim($config['passphrase']));
    }
    if (!hash_equals(md5($toSign), (string) ($post['signature'] ?? ''))) {
        return 'signature mismatch';
    }

    // 2 it came from a PayFast server, behind Render's proxy every forwarded address is checked
    // that header can be faked which is why step 4 has the final word
    if (!payfastFromValidHost()) {
        return 'not sent from a PayFast server';
    }

    // 3 the amount matches what the donor was asked to pay
    if (abs((float) ($post['amount_gross'] ?? 0) - $expectedAmount) > 0.01) {
        return 'amount mismatch';
    }

    // 4 ask PayFast to confirm it sent this notification
    $curl = curl_init('https://' . $config['host'] . '/eng/query/validate');
    curl_setopt_array($curl, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $paramString,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($curl);
    $curlError = curl_error($curl);

    if ($response === false) {
        return 'could not reach PayFast to confirm: ' . $curlError;
    }
    if (trim($response) !== 'VALID') {
        return 'PayFast did not confirm the notification';
    }

    return null;
}

// check the sender is one of PayFast's published addresses or its current host addresses
function payfastFromValidHost(): bool
{
    $validRanges = ['197.97.145.144/28', '41.74.179.192/27', '102.216.36.0/28', '102.216.36.128/28', '144.126.193.139/32'];
    foreach (['www.payfast.co.za', 'sandbox.payfast.co.za', 'w1w.payfast.co.za', 'w2w.payfast.co.za'] as $host) {
        foreach (gethostbynamel($host) ?: [] as $ip) {
            $validRanges[] = $ip . '/32';
        }
    }

    $senders = [$_SERVER['REMOTE_ADDR'] ?? ''];
    foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '') as $forwarded) {
        $senders[] = trim($forwarded);
    }

    foreach ($senders as $sender) {
        $senderLong = ip2long($sender);
        if ($senderLong === false) {
            continue;
        }
        foreach ($validRanges as $range) {
            [$subnet, $bits] = explode('/', $range);
            $mask = -1 << (32 - (int) $bits);
            if (($senderLong & $mask) === (ip2long($subnet) & $mask)) {
                return true;
            }
        }
    }

    return false;
}
