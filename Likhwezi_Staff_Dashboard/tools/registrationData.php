<?php
/*
 * REGISTRATION DATA
 *
 * Role-neutral data helper for the staff Registrations page.
 */

function getRegistrationProgrammes()
{
    return ['Coding', 'Artificial Intelligence', 'Robotics', 'Hackathons'];
}

/*
 * Age in whole years from a Y-m-d date of birth.
 */
function calculateAge($dateOfBirth)
{
    $dob = DateTime::createFromFormat('Y-m-d', $dateOfBirth);

    if (!$dob) {
        return null;
    }

    return $dob->diff(new DateTime('today'))->y;
}

/*
 * SAMPLE DATA
 */
function getRegistrationRecords()
{
    $daysAgo = function ($days, $time = '10:15:00') {
        return date('Y-m-d', strtotime("-{$days} days")) . ' ' . $time;
    };

    $yearsAgo = function ($years, $extraDays = 0) {
        return date('Y-m-d', strtotime("-{$years} years -{$extraDays} days"));
    };

    $adult = [
        'guardianName' => null,
        'guardianSurname' => null,
        'guardianRelationship' => null,
        'guardianPhone' => null,
        'guardianEmail' => null,
        'guardianConsentConfirmation' => false,
        'guardianConsentGivenAt' => null
    ];

    $records = [
        [
            'registrationID' => 1001, 'firstName' => 'Lindiwe', 'lastName' => 'Dlamini',
            'dateOfBirth' => $yearsAgo(15, 40), 'email' => null, 'phoneNumber' => '0721234567',
            'programme' => 'Robotics', 'registeredAt' => $daysAgo(0, '08:22:10'),
            'mediaConsent' => true,
            'guardianName' => 'Nomsa', 'guardianSurname' => 'Dlamini', 'guardianRelationship' => 'Mother',
            'guardianPhone' => '0829876543', 'guardianEmail' => 'nomsa.dlamini@example.co.za',
            'guardianConsentConfirmation' => true, 'guardianConsentGivenAt' => $daysAgo(0, '08:22:10')
        ],
        [
            'registrationID' => 1002, 'firstName' => 'Sipho', 'lastName' => 'Mahlangu',
            'dateOfBirth' => $yearsAgo(22, 100), 'email' => 'sipho.m@example.com', 'phoneNumber' => '0612345678',
            'programme' => 'Coding', 'registeredAt' => $daysAgo(2, '09:05:44'),
            'mediaConsent' => false
        ] + $adult,
        [
            'registrationID' => 1003, 'firstName' => 'Ayanda', 'lastName' => 'Khumalo',
            'dateOfBirth' => $yearsAgo(17, 300), 'email' => 'ayanda.k@example.com', 'phoneNumber' => null,
            'programme' => 'Artificial Intelligence', 'registeredAt' => $daysAgo(3, '16:40:02'),
            'mediaConsent' => true,
            'guardianName' => 'Themba', 'guardianSurname' => 'Khumalo', 'guardianRelationship' => 'Father',
            'guardianPhone' => '0731112222', 'guardianEmail' => null,
            'guardianConsentConfirmation' => true, 'guardianConsentGivenAt' => $daysAgo(3, '16:40:02')
        ],
        [
            'registrationID' => 1004, 'firstName' => 'Karabo', 'lastName' => 'Molefe',
            'dateOfBirth' => $yearsAgo(34, 20), 'email' => 'karabo.molefe@example.co.za', 'phoneNumber' => '0845556666',
            'programme' => 'Hackathons', 'registeredAt' => $daysAgo(5, '11:12:30'),
            'mediaConsent' => true
        ] + $adult,
        [
            'registrationID' => 1005, 'firstName' => 'Thabo', 'lastName' => 'Nkosi',
            'dateOfBirth' => $yearsAgo(12, 60), 'email' => null, 'phoneNumber' => '0798887777',
            'programme' => 'Coding', 'registeredAt' => $daysAgo(8, '13:01:19'),
            'mediaConsent' => false,
            'guardianName' => 'Zanele', 'guardianSurname' => 'Nkosi', 'guardianRelationship' => 'Grandmother',
            'guardianPhone' => null, 'guardianEmail' => 'zanele.nkosi@example.com',
            'guardianConsentConfirmation' => true, 'guardianConsentGivenAt' => $daysAgo(8, '13:01:19')
        ],
        [
            'registrationID' => 1006, 'firstName' => 'Naledi', 'lastName' => 'Mokoena',
            'dateOfBirth' => $yearsAgo(41, 5), 'email' => 'naledi.mokoena@example.com', 'phoneNumber' => null,
            'programme' => 'Artificial Intelligence', 'registeredAt' => $daysAgo(12, '08:47:55'),
            'mediaConsent' => true
        ] + $adult,
        [
            'registrationID' => 1007, 'firstName' => 'Bongani', 'lastName' => 'Zulu',
            'dateOfBirth' => $yearsAgo(19, 200), 'email' => 'bongani.zulu@example.com', 'phoneNumber' => '0823334444',
            'programme' => 'Robotics', 'registeredAt' => $daysAgo(16, '15:30:00'),
            'mediaConsent' => false
        ] + $adult,
        [
            'registrationID' => 1008, 'firstName' => 'Palesa', 'lastName' => 'Mabaso',
            'dateOfBirth' => $yearsAgo(14, 150), 'email' => null, 'phoneNumber' => '0762223333',
            'programme' => 'Hackathons', 'registeredAt' => $daysAgo(21, '10:10:10'),
            'mediaConsent' => true,
            'guardianName' => 'Lerato', 'guardianSurname' => 'Mabaso', 'guardianRelationship' => 'Aunt',
            'guardianPhone' => '0715550000', 'guardianEmail' => 'lerato.mabaso@example.com',
            'guardianConsentConfirmation' => true, 'guardianConsentGivenAt' => $daysAgo(21, '10:10:10')
        ],
        [
            'registrationID' => 1009, 'firstName' => 'Mpho', 'lastName' => 'Sithole',
            'dateOfBirth' => $yearsAgo(27, 80), 'email' => 'mpho.sithole@example.co.za', 'phoneNumber' => '0634445555',
            'programme' => 'Coding', 'registeredAt' => $daysAgo(30, '12:00:00'),
            'mediaConsent' => true
        ] + $adult,
        [
            'registrationID' => 1010, 'firstName' => 'Kagiso', 'lastName' => 'Ndlovu',
            'dateOfBirth' => $yearsAgo(16, 10), 'email' => 'kagiso.n@example.com', 'phoneNumber' => '0817778888',
            'programme' => 'Artificial Intelligence', 'registeredAt' => $daysAgo(38, '17:25:41'),
            'mediaConsent' => false,
            'guardianName' => 'Peter', 'guardianSurname' => 'Ndlovu', 'guardianRelationship' => 'Father',
            'guardianPhone' => '0839990000', 'guardianEmail' => null,
            'guardianConsentConfirmation' => true, 'guardianConsentGivenAt' => $daysAgo(38, '17:25:41')
        ],
        [
            'registrationID' => 1011, 'firstName' => 'Refilwe', 'lastName' => 'Baloyi',
            'dateOfBirth' => $yearsAgo(23, 30), 'email' => 'refilwe.baloyi@example.com', 'phoneNumber' => null,
            'programme' => 'Robotics', 'registeredAt' => $daysAgo(45, '09:40:00'),
            'mediaConsent' => true
        ] + $adult,
        [
            'registrationID' => 1012, 'firstName' => 'Lwazi', 'lastName' => 'Shabalala',
            'dateOfBirth' => $yearsAgo(31, 220), 'email' => 'lwazi.s@example.com', 'phoneNumber' => '0791231234',
            'programme' => 'Hackathons', 'registeredAt' => $daysAgo(52, '14:14:14'),
            'mediaConsent' => true
        ] + $adult,

        /* Archived samples. */
        [
            'registrationID' => 990, 'firstName' => 'Tumelo', 'lastName' => 'Phiri',
            'dateOfBirth' => $yearsAgo(20, 90), 'email' => 'tumelo.phiri@example.com', 'phoneNumber' => null,
            'programme' => 'Coding', 'registeredAt' => $daysAgo(90, '10:00:00'),
            'mediaConsent' => false,
            'isArchived' => true, 'archivedAt' => $daysAgo(20, '11:30:00'), 'archivedBy' => 'Name Surname'
        ] + $adult,
        [
            'registrationID' => 991, 'firstName' => 'Zinhle', 'lastName' => 'Mthembu',
            'dateOfBirth' => $yearsAgo(13, 70), 'email' => null, 'phoneNumber' => '0724443333',
            'programme' => 'Robotics', 'registeredAt' => $daysAgo(120, '15:45:00'),
            'mediaConsent' => true,
            'guardianName' => 'Busi', 'guardianSurname' => 'Mthembu', 'guardianRelationship' => 'Mother',
            'guardianPhone' => '0786665555', 'guardianEmail' => null,
            'guardianConsentConfirmation' => true, 'guardianConsentGivenAt' => $daysAgo(120, '15:45:00'),
            'isArchived' => true, 'archivedAt' => $daysAgo(9, '08:20:00'), 'archivedBy' => 'Name Surname'
        ]
    ];

    /* Fill in derived and default values so every record has the same keys. */
    foreach ($records as &$record) {
        $record['age'] = calculateAge($record['dateOfBirth']);
        $record['isMinor'] = $record['age'] !== null && $record['age'] < 18;
        $record['consentConfirmation'] = true;          // mandatory on cym.php
        $record['consentGivenAt'] = $record['registeredAt'];
        $record['isArchived'] = $record['isArchived'] ?? false;
        $record['archivedAt'] = $record['archivedAt'] ?? null;
        $record['archivedBy'] = $record['archivedBy'] ?? null;
    }
    unset($record);

    return $records;
}

/*
 * Values for the three summary cards. Active (non-archived) records only.
 */
function getRegistrationStats()
{
    $active = array_filter(getRegistrationRecords(), fn($r) => !$r['isArchived']);
    $monthStart = date('Y-m-01 00:00:00');

    return [
        'total' => count($active),
        'thisMonth' => count(array_filter($active, fn($r) => $r['registeredAt'] >= $monthStart)),
        'minors' => count(array_filter($active, fn($r) => $r['isMinor']))
    ];
}

/*
 * Columns the staff table can be sorted by, with the direction used when
 * a column is first clicked. Text and age start A-Z / youngest first;
 * dates and media consent start newest / "given" first.
 */
function getRegistrationSortColumns()
{
    return [
        'name' => 'asc',
        'programme' => 'asc',
        'age' => 'asc',
        'date' => 'desc',
        'media' => 'desc'
    ];
}

/*
 * Reads and cleans the filter values from a request array (normally $_GET).
 * Anything unexpected falls back to its default, so the page never has to
 * trust raw input.
 */
function readRegistrationFilters(array $input)
{
    $view = ($input['view'] ?? '') === 'archived' ? 'archived' : 'active';

    $programme = $input['programme'] ?? '';
    if (!in_array($programme, getRegistrationProgrammes(), true)) {
        $programme = '';
    }

    $ageGroup = $input['age'] ?? '';
    if (!in_array($ageGroup, ['minor', 'adult'], true)) {
        $ageGroup = '';
    }

    $validDate = function ($value) {
        $d = DateTime::createFromFormat('Y-m-d', (string) $value);
        return ($d && $d->format('Y-m-d') === $value) ? $value : '';
    };

    /* Default: newest first. Unknown columns fall back to the default. */
    $sort = $input['sort'] ?? 'date';
    if (!array_key_exists($sort, getRegistrationSortColumns())) {
        $sort = 'date';
    }

    $dir = ($input['dir'] ?? '') === 'asc' ? 'asc' : (($input['dir'] ?? '') === 'desc' ? 'desc' : getRegistrationSortColumns()[$sort]);

    return [
        'view' => $view,
        'q' => trim(substr((string) ($input['q'] ?? ''), 0, 100)),
        'programme' => $programme,
        'age' => $ageGroup,
        'from' => $validDate($input['from'] ?? ''),
        'to' => $validDate($input['to'] ?? ''),
        'sort' => $sort,
        'dir' => $dir,
        'page' => max(1, (int) ($input['page'] ?? 1))
    ];
}

/*
 * Filtered, sorted, paginated registrations.
 *
 * Returns:
 *     rows        records for the current page
 *     totalRows   matching records across all pages
 *     page        current page (clamped to a valid page)
 *     pages       total pages
 *     counts      ['active' => n, 'archived' => n] for the view toggle
 */
function getRegistrations(array $filters, $perPage = 10)
{
    $all = getRegistrationRecords();

    $counts = [
        'active' => count(array_filter($all, fn($r) => !$r['isArchived'])),
        'archived' => count(array_filter($all, fn($r) => $r['isArchived']))
    ];

    $wantArchived = $filters['view'] === 'archived';
    $search = $filters['q'];

    $rows = array_filter($all, function ($r) use ($filters, $wantArchived, $search) {
        if ($r['isArchived'] !== $wantArchived) {
            return false;
        }

        if ($filters['programme'] !== '' && $r['programme'] !== $filters['programme']) {
            return false;
        }

        if ($filters['age'] === 'minor' && !$r['isMinor']) {
            return false;
        }

        if ($filters['age'] === 'adult' && $r['isMinor']) {
            return false;
        }

        $registeredDate = substr($r['registeredAt'], 0, 10);

        if ($filters['from'] !== '' && $registeredDate < $filters['from']) {
            return false;
        }

        if ($filters['to'] !== '' && $registeredDate > $filters['to']) {
            return false;
        }

        if ($search !== '') {
            /* Phone is matched without spaces so "072 123" finds 0721234567. */
            $haystack = implode(' ', [
                $r['firstName'] . ' ' . $r['lastName'], $r['email'] ?? '', $r['phoneNumber'] ?? ''
            ]);
            $searchDigits = str_replace(' ', '', $search);

            if (stripos($haystack, $search) === false
                && ($searchDigits === '' || !ctype_digit($searchDigits) || stripos($haystack, $searchDigits) === false)) {
                return false;
            }
        }

        return true;
    });

    /*
     * SORTING
     */
    $dateKey = $wantArchived ? 'archivedAt' : 'registeredAt';

    $compare = function ($a, $b) use ($filters, $dateKey) {
        switch ($filters['sort']) {
            case 'name':
                $result = strcasecmp($a['firstName'] . ' ' . $a['lastName'], $b['firstName'] . ' ' . $b['lastName']);
                break;
            case 'programme':
                $result = strcmp($a['programme'], $b['programme']);
                break;
            case 'age':
                $result = strcmp($b['dateOfBirth'], $a['dateOfBirth']);
                break;
            case 'media':
                $result = (int) $a['mediaConsent'] <=> (int) $b['mediaConsent'];
                break;
            default:
                $result = strcmp($a[$dateKey], $b[$dateKey]);
        }

        if ($filters['dir'] === 'desc') {
            $result = -$result;
        }

        /* Ties (e.g. same programme) always show newest first, so the order is stable. */
        return $result !== 0 ? $result : strcmp($b[$dateKey], $a[$dateKey]);
    };

    usort($rows, $compare);

    $totalRows = count($rows);
    $pages = max(1, (int) ceil($totalRows / $perPage));
    $page = min($filters['page'], $pages);

    return [
        'rows' => array_slice($rows, ($page - 1) * $perPage, $perPage),
        'totalRows' => $totalRows,
        'page' => $page,
        'pages' => $pages,
        'counts' => $counts
    ];
}
