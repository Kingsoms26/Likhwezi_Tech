// emailChooser.js runs every email link on the public site and the staff portal
// tries the email app first and offers gmail or outlook when no app opens

document.addEventListener('click', function (event) {
    const link = event.target.closest('a[href^="mailto:"]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey) return;

    event.preventDefault();

    const mailto = link.getAttribute('href');
    let appOpened = false;

    // the page loses focus or is hidden when an email app opens
    const opened = function () { appOpened = true; };
    window.addEventListener('blur', opened, { once: true });
    document.addEventListener('visibilitychange', opened, { once: true });

    window.location.href = mailto;

    // nothing opened after a second so there is no email app, show the choices
    setTimeout(function () {
        window.removeEventListener('blur', opened);
        document.removeEventListener('visibilitychange', opened);

        if (!appOpened) showEmailChooser(mailto, link);
    }, 1000);
});

// split a mailto link into the address, subject and body
function parseMailto(mailto) {
    const [address, query] = mailto.slice('mailto:'.length).split('?');
    const params = new URLSearchParams(query || '');

    return {
        to: decodeURIComponent(address),
        subject: params.get('subject') || '',
        body: params.get('body') || ''
    };
}

// build a web mail link with the values encoded the same way
function webMailUrl(base, values) {
    return base + Object.entries(values)
        .map(function ([name, value]) { return name + '=' + encodeURIComponent(value); })
        .join('&');
}

// popup with gmail, outlook and copy the address
function showEmailChooser(mailto, link) {
    const email = parseMailto(mailto);

    const gmail = webMailUrl('https://mail.google.com/mail/?', {
        view: 'cm', fs: '1', to: email.to, su: email.subject, body: email.body
    });
    const outlook = webMailUrl('https://outlook.live.com/mail/0/deeplink/compose?', {
        to: email.to, subject: email.subject, body: email.body
    });

    const dialog = document.createElement('dialog');
    dialog.className = 'email-chooser';
    dialog.setAttribute('aria-labelledby', 'email-chooser-title');
    dialog.innerHTML =
        '<h2 id="email-chooser-title">Send an email</h2>' +
        '<p>No email app opened on this device. Choose where to write your email to <strong></strong>.</p>' +
        '<div class="email-chooser-options">' +
            '<a class="email-chooser-option" target="_blank" rel="noopener" data-email-web="gmail"><i class="bi bi-google" aria-hidden="true"></i> Gmail</a>' +
            '<a class="email-chooser-option" target="_blank" rel="noopener" data-email-web="outlook"><i class="bi bi-microsoft" aria-hidden="true"></i> Outlook</a>' +
            '<button type="button" class="email-chooser-option" data-email-copy><i class="bi bi-clipboard" aria-hidden="true"></i> Copy address</button>' +
        '</div>' +
        '<button type="button" class="email-chooser-close" data-email-close>Cancel</button>';

    dialog.querySelector('strong').textContent = email.to;
    dialog.querySelector('[data-email-web="gmail"]').href = gmail;
    dialog.querySelector('[data-email-web="outlook"]').href = outlook;

    // close after picking gmail or outlook
    dialog.querySelectorAll('[data-email-web]').forEach(function (option) {
        option.addEventListener('click', function () { dialog.close(); });
    });

    // copy the address
    const copyButton = dialog.querySelector('[data-email-copy]');
    copyButton.addEventListener('click', function () {
        if (!navigator.clipboard) return;

        navigator.clipboard.writeText(email.to).then(function () {
            copyButton.lastChild.textContent = ' Copied';
        });
    });

    dialog.querySelector('[data-email-close]').addEventListener('click', function () { dialog.close(); });

    // clicking outside the card closes the popup
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) dialog.close();
    });

    dialog.addEventListener('close', function () { dialog.remove(); });

    // inside an open dialog or modal so it stays on top and can be clicked
    (link.closest('dialog, .modal') || document.body).appendChild(dialog);
    dialog.showModal();
}
