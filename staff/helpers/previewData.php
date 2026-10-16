<?php
// Local staff-page fixtures for the display-only preview mode.

function staffPreviewDate(int $daysAgo = 0): string
{
    return (new DateTimeImmutable('today'))
        ->modify(($daysAgo >= 0 ? '-' : '+') . abs($daysAgo) . ' days')
        ->format('Y-m-d H:i:s');
}

function staffPreviewEnquiries(int $accountID): array
{
    $records = [
        [99001, 'Amara Ndlovu', 'Kasi Code Hub', 'amara@example.test', '082 555 0101', 'We are looking for coding sessions for learners at our community centre.', 'online', 'new', false, null, null, 1],
        [99002, 'Thabo Mokoena', 'Bright Futures School', 'thabo@example.test', '082 555 0102', 'Could you share details about the robotics programme and term dates?', 'in-person', 'new', false, null, null, 2],
        [99003, 'Lerato Dlamini', '', 'lerato@example.test', '082 555 0103', 'Please send a proposal for a digital skills workshop.', 'online', 'new', false, $accountID, 'You', 3],
        [99004, 'Sipho Khumalo', 'Northside Library', 'sipho@example.test', '082 555 0104', 'We would like to host a coding club at our library.', 'in-person', 'contacted', false, $accountID, 'You', 5],
        [99005, 'Naledi Jacobs', 'Future Makers', 'naledi@example.test', '082 555 0105', 'Following up on our request for an introductory workshop.', 'online', 'contacted', false, 99090, 'Mpho Dube', 8],
        [99006, 'Palesa Molefe', '', 'palesa@example.test', '082 555 0106', 'Thank you for the programme information; we are ready to proceed.', 'online', 'closed', false, $accountID, 'You', 12],
        [99007, 'Kabelo Nkosi', 'Eastview Youth Centre', 'kabelo@example.test', '082 555 0107', 'Please contact us about a school holiday coding camp.', 'in-person', 'closed', false, 99090, 'Mpho Dube', 20],
        [99008, 'Zanele Maseko', 'Community Tech Lab', 'zanele@example.test', '082 555 0108', 'We are interested in partnering on a digital inclusion event.', 'online', 'new', true, null, null, 31],
    ];

    return array_map(static fn ($row) => [
        'enquiryID' => $row[0],
        'name' => $row[1],
        'companyName' => $row[2],
        'email' => $row[3],
        'phoneNumber' => $row[4],
        'description' => $row[5],
        'meetingType' => $row[6],
        'status' => $row[7],
        'isArchived' => $row[8],
        'handledBy' => $row[9],
        'handledByName' => $row[10],
        'dateCreated' => staffPreviewDate($row[11]),
    ], $records);
}

function staffPreviewRegistrations(): array
{
    $people = [
        [99101, 'Ayanda', 'Zulu', 16, 'ayanda@example.test', '083 555 0101', 'Coding', true],
        [99102, 'Lwazi', 'Peters', 21, 'lwazi@example.test', '083 555 0102', 'Robotics', false],
        [99103, 'Nandi', 'Mthembu', 17, 'nandi@example.test', '083 555 0103', 'Artificial Intelligence', true],
        [99104, 'Sibusiso', 'Botha', 14, 'sibusiso@example.test', '083 555 0104', 'Coding', true],
        [99105, 'Keisha', 'Dube', 24, 'keisha@example.test', '083 555 0105', 'Hackathons', false],
        [99106, 'Musa', 'Khumalo', 19, 'musa@example.test', '083 555 0106', 'Robotics', true],
        [99107, 'Refilwe', 'Mokoena', 15, 'refilwe@example.test', '083 555 0107', 'Coding', true],
        [99108, 'Themba', 'Naidoo', 32, 'themba@example.test', '083 555 0108', 'Artificial Intelligence', false],
        [99109, 'Karabo', 'Molefe', 16, 'karabo@example.test', '083 555 0109', 'Robotics', true],
        [99110, 'Zola', 'Adams', 27, 'zola@example.test', '083 555 0110', 'Hackathons', false],
        [99111, 'Dineo', 'Pillay', 13, 'dineo@example.test', '083 555 0111', 'Coding', false],
        [99112, 'Bongani', 'Maseko', 18, 'bongani@example.test', '083 555 0112', 'Robotics', true],
    ];

    return array_map(static function ($person, $index) {
        [$id, $first, $last, $age, $email, $phone, $programme, $mediaConsent] = $person;
        $minor = $age < 18;
        $registeredAt = staffPreviewDate($index + 1);

        return [
            'registrationID' => $id,
            'firstName' => $first,
            'lastName' => $last,
            'age' => $age,
            'email' => $email,
            'phoneNumber' => $phone,
            'programme' => $programme,
            'consentGivenAt' => $registeredAt,
            'mediaConsent' => $mediaConsent,
            'guardianName' => $minor ? 'Demo Guardian' : null,
            'guardianLastName' => $minor ? 'Example' : null,
            'guardianRelationship' => $minor ? 'Parent' : null,
            'guardianEmail' => $minor ? 'guardian@example.test' : null,
            'guardianPhoneNumber' => $minor ? '084 555 0199' : null,
            'guardianConsentGivenAt' => $minor ? $registeredAt : null,
            'guardianCommunicationConsent' => $minor,
            'isArchived' => $index === 11,
            'registeredAt' => $registeredAt,
            'archivedAt' => $index === 11 ? staffPreviewDate(0) : null,
            'archivedBy' => $index === 11 ? 'Mpho Dube' : null,
            'isMinor' => $minor,
        ];
    }, $people, array_keys($people));
}

function staffPreviewFilteredRegistrations(array $filters): array
{
    $rows = array_values(array_filter(
        staffPreviewRegistrations(),
        fn ($row) => $row['isArchived'] === ($filters['view'] === 'archived')
    ));

    if ($filters['programme'] !== '') {
        $rows = array_values(array_filter($rows, fn ($row) => $row['programme'] === $filters['programme']));
    }
    if ($filters['age'] === 'minor') {
        $rows = array_values(array_filter($rows, fn ($row) => $row['isMinor']));
    } elseif ($filters['age'] === 'adult') {
        $rows = array_values(array_filter($rows, fn ($row) => !$row['isMinor']));
    }
    if ($filters['q'] !== '') {
        $needle = mb_strtolower($filters['q']);
        $rows = array_values(array_filter($rows, fn ($row) => str_contains(
            mb_strtolower($row['firstName'] . ' ' . $row['lastName'] . ' ' . $row['email'] . ' ' . $row['phoneNumber']),
            $needle
        )));
    }
    if ($filters['from'] !== '') {
        $rows = array_values(array_filter($rows, fn ($row) => substr($row['registeredAt'], 0, 10) >= $filters['from']));
    }
    if ($filters['to'] !== '') {
        $rows = array_values(array_filter($rows, fn ($row) => substr($row['registeredAt'], 0, 10) <= $filters['to']));
    }

    $sortKeys = [
        'name' => fn ($row) => $row['firstName'] . ' ' . $row['lastName'],
        'programme' => fn ($row) => $row['programme'],
        'age' => fn ($row) => $row['age'],
        'date' => fn ($row) => $row['registeredAt'],
        'media' => fn ($row) => $row['mediaConsent'],
    ];
    $sortKey = $sortKeys[$filters['sort']] ?? $sortKeys['date'];
    usort($rows, static function ($a, $b) use ($sortKey, $filters) {
        $comparison = $sortKey($a) <=> $sortKey($b);
        return ($filters['dir'] === 'asc' ? $comparison : -$comparison)
            ?: $b['registrationID'] <=> $a['registrationID'];
    });

    return $rows;
}

function staffPreviewRegistrationResult(array $filters, int $perPage = 10): array
{
    $all = staffPreviewRegistrations();
    $filtered = staffPreviewFilteredRegistrations($filters);
    $total = count($filtered);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, (int) $filters['page']), $pages);
    $offset = ($page - 1) * $perPage;

    return [
        'rows' => array_slice($filtered, $offset, $perPage),
        'totalRows' => $total,
        'page' => $page,
        'pages' => $pages,
        'counts' => [
            'active' => count(array_filter($all, fn ($row) => !$row['isArchived'])),
            'archived' => count(array_filter($all, fn ($row) => $row['isArchived'])),
        ],
    ];
}

function staffPreviewRegistrationStats(): array
{
    $active = array_values(array_filter(staffPreviewRegistrations(), fn ($row) => !$row['isArchived']));
    return [
        'total' => count($active),
        'thisMonth' => count($active),
        'minors' => count(array_filter($active, fn ($row) => $row['isMinor'])),
        'lastSevenDays' => count(array_filter($active, fn ($row) => (new DateTimeImmutable($row['registeredAt'])) >= new DateTimeImmutable('-6 days'))),
    ];
}

function staffPreviewCampaigns(): array
{
    $rows = [
        [99201, 'School Lab Equipment', 'Equip local schools with laptops, robotics kits and reliable learning tools.', 50000, 'active', false, 38250, 7850, 18, 3, 2],
        [99202, 'Youth Coding Kits', 'Provide beginner-friendly coding kits for after-school clubs.', 35000, 'active', false, 22400, 6100, 12, 2, 4],
        [99203, 'Robotics Workshop', 'Fund a hands-on robotics workshop series for young innovators.', 40000, 'active', false, 18800, 4200, 9, 1, 7],
        [99204, 'Digital Skills Bus', 'Bring practical digital skills training to underserved communities.', 80000, 'active', false, 21600, 3900, 11, 2, 9],
        [99205, 'Community Wi-Fi', 'Connect community learning spaces with dependable internet access.', 25000, 'draft', false, 0, 0, 0, 0, null],
        [99206, 'Girls in Tech Day', 'A celebration and mentoring day for girls exploring technology.', 18000, 'closed', false, 18000, 2500, 14, 4, 40],
        [99207, 'Mobile Maker Space', 'A travelling maker space for rural schools.', 60000, 'closed', true, 44200, 0, 21, 3, 70],
    ];

    return array_map(static function ($row) {
        [$id, $name, $description, $goal, $status, $archived, $raised, $recent, $donations, $photos, $lastDays] = $row;
        return [
            'campaignID' => $id,
            'name' => $name,
            'description' => $description,
            'goal' => (float) $goal,
            'status' => $status,
            'isArchived' => $archived,
            'raised' => (float) $raised,
            'raisedLast30Days' => (float) $recent,
            'donations' => $donations,
            'lastDonation' => $lastDays === null ? null : staffPreviewDate($lastDays),
            'photoCount' => $photos,
            'coverImage' => $photos ? 'assets/images/home-img-2.webp' : null,
            'percentage' => $goal > 0 ? (int) min(100, floor($raised / $goal * 100)) : 0,
            'createdByName' => 'Mpho Dube',
        ];
    }, $rows);
}

function staffPreviewEvents(): array
{
    $today = new DateTimeImmutable('today');
    $events = [
        ['99301', 'CYM Coding Workshop', 'A practical introduction to building a first web page.', 2, false, 0],
        ['99302', 'Robotics Open Day', 'Young people can explore sensors, motors and simple robot builds.', 5, false, 0],
        ['99303', 'Digital Skills for Educators', 'A hands-on session on using digital tools in the classroom.', 9, false, 0],
        ['99304', 'Community Showcase', 'Learners share projects created during the winter programme.', -8, false, 0],
        ['99305', 'Women in Technology Panel', 'A panel discussion with local technology professionals.', -24, false, 0],
        ['99306', 'Mobile Maker Space Launch', 'A travelling maker space launch event.', 35, true, 0],
    ];

    return array_map(static function ($event) use ($today) {
        [$id, $name, $description, $offset, $archived, $photos] = $event;
        $date = $today->modify(($offset < 0 ? '' : '+') . $offset . ' days');
        $daysAway = $offset;

        return [
            'eventID' => (int) $id,
            'name' => $name,
            'description' => $description,
            'eventDate' => $date->format('Y-m-d'),
            'dateToBeConfirmed' => false,
            'isArchived' => $archived,
            'daysAway' => $daysAway,
            'creatorFirstName' => 'Mpho',
            'creatorLastName' => 'Dube',
            'creatorUsername' => 'mpho',
            'photoCount' => $photos,
            'coverImage' => null,
            'createdByName' => 'Mpho Dube',
            'timing' => $daysAway < 0 ? 'past' : 'upcoming',
            'when' => match (true) {
                $daysAway === 0 => 'Today',
                $daysAway === 1 => 'Tomorrow',
                $daysAway > 1 => "In $daysAway days",
                $daysAway === -1 => 'Yesterday',
                default => abs($daysAway) . ' days ago',
            },
        ];
    }, $events);
}

function staffPreviewAnnouncements(): array
{
    return [
        [
            'announcementID' => 99401, 'title' => 'Coding workshop registrations are open',
            'message' => 'Bookings are now open for our upcoming CYM coding workshop. Share the registration link with interested learners.',
            'audience' => 'everyone', 'linkURL' => 'https://example.test/cym.php',
            'startDate' => staffPreviewDate(1), 'endDate' => staffPreviewDate(-14),
            'isActive' => 1, 'dateCreated' => staffPreviewDate(2),
            'firstName' => 'Mpho', 'lastName' => 'Dube', 'username' => 'mpho',
        ],
        [
            'announcementID' => 99402, 'title' => 'Staff planning meeting',
            'message' => 'Please review the updated programme calendar before Friday’s planning session.',
            'audience' => 'staff', 'linkURL' => null,
            'startDate' => null, 'endDate' => null, 'isActive' => 1,
            'dateCreated' => staffPreviewDate(4),
            'firstName' => 'Mpho', 'lastName' => 'Dube', 'username' => 'mpho',
        ],
        [
            'announcementID' => 99403, 'title' => 'Digital Skills Bus update',
            'message' => 'The campaign page has been refreshed with the latest progress update.',
            'audience' => 'website', 'linkURL' => 'https://example.test/campaign.php',
            'startDate' => null, 'endDate' => null, 'isActive' => 0,
            'dateCreated' => staffPreviewDate(12),
            'firstName' => 'Mpho', 'lastName' => 'Dube', 'username' => 'mpho',
        ],
    ];
}

function staffPreviewNotifications(): array
{
    $notifications = [
        [
            'type' => 'announcement', 'level' => 'info',
            'title' => 'Coding workshop registrations are open',
            'detail' => 'Bookings are open for the next CYM coding workshop.',
            'link' => 'manageEvents.php',
        ],
        [
            'type' => 'enquiry', 'level' => 'warning',
            'title' => '3 new enquiries need attention',
            'detail' => 'Review the latest messages from prospective partners.',
            'link' => 'manageEnquiries.php',
        ],
        [
            'type' => 'event', 'level' => 'info',
            'title' => 'Robotics Open Day is coming up',
            'detail' => 'The event is scheduled in 5 days.',
            'link' => 'manageEvents.php',
        ],
    ];

    foreach ($notifications as &$notification) {
        $notification['id'] = notificationID($notification);
    }
    unset($notification);

    return $notifications;
}
