<?php
/*
 * SHARED DASHBOARD DATA
 *
 * This file is responsible for getting dashboard information.
 *
 * IMPORTANT:
 *     This file returns data.
 *     It does NOT render HTML.
 *
 * The same function can later be used by multiple dashboards.
 *
 * Example:
 *
 *     $stats = getDashboardStats($conn);
 *
 * Then:
 *
 *     $stats['activeEnquiries']
 *     $stats['activeCampaigns']
 *     $stats['fundsRaised']
 *
 * CURRENTLY:
 *     Sample data is returned so the framework can run without MySQL.
 *
 * LATER:
 *     Replace the sample section with SQL using the project's existing
 *     mysqli connection from tools/dbConnection.php.
 */

function getDashboardStats()
{
    /*
     * SAMPLE DATA
     *
     * Keep the returned structure the same when replacing this with SQL.
     * Dashboard pages should not need to change just because the source
     * of the data changes from sample values to MySQL.
     */
    return [
        'activeEnquiries' => 8,

        'activeCampaigns' => 5,

        'fundsRaised' => 245000.00,

        'recentEnquiries' => [
            [
                'subject' => 'Website enquiry',
                'customer' => 'Customer One',
                'status' => 'New'
            ],
            [
                'subject' => 'Partnership enquiry',
                'customer' => 'Customer Two',
                'status' => 'In Progress'
            ],
            [
                'subject' => 'Programme enquiry',
                'customer' => 'Customer Three',
                'status' => 'New'
            ]
        ],

        'registrationInterest' => [
            [
                'name' => 'Education',
                'percentage' => 65
            ],
            [
                'name' => 'Technology',
                'percentage' => 45
            ],
            [
                'name' => 'Community',
                'percentage' => 30
            ]
        ],

        'campaigns' => [
            [
                'name' => 'Campaign One',
                'percentage' => 25
            ],
            [
                'name' => 'Campaign Two',
                'percentage' => 45
            ],
            [
                'name' => 'Campaign Three',
                'percentage' => 70
            ],
            [
                'name' => 'Campaign Four',
                'percentage' => 90
            ]
        ]
    ];
}

/*
 * Page-specific data can later go into separate helpers, for example:
 *
 *     tools/marketingData.php
 *     tools/customerServiceData.php
 */
