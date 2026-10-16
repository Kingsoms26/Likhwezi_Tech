# Dashboard Change Log and Line Guide

This guide maps the dashboard implementation and the changes made for the shared staff/admin dashboard and phone navigation. Line ranges refer to the current versions of the files and may move as code is edited.

## `staff/adminDashboard.php`

| Lines | What is there |
| --- | --- |
| 1-9 | Loads security and database helpers, verifies the session, and restricts this entry point to administrators. |
| 11-14 | Forwards administrators to the shared staff dashboard and preserves query-string options such as the donation chart period. |

## `staff/staffDashboard.php`

| Lines | What is there |
| --- | --- |
| 1-18 | Loads the dashboard dependencies, checks the signed-in account and role, and validates the requested donation-chart period. |
| 20-71 | Supplies the shared dashboard's sample enquiries, registrations, campaigns, donations, and upcoming events. The alternative branch retains the live-data query path. |
| 73-89 | Converts donation values into SVG chart coordinates. |
| 91-110 | Builds the selected weekly or monthly donation series, total, chart points, and labels. |
| 111-134 | Groups registration totals and allocates registration chart cells and colors. |
| 136-141 | Sets the page title and active navigation item, then includes the shared dashboard shell. |
| 144-155 | Starts the overview hero and renders its sample-data label and introduction. |
| 157-213 | Renders the donation summary, period selector, chart, and totals. |
| 215-243 | Renders the enquiries highlight card, status segments, and counts. |
| 245-277 | Renders the upcoming-events highlight card and its event links. |
| 279-308 | Renders the registrations highlight card, chart, and legend. |
| 310-337 | Renders the active campaign progress card and chart. |
| 339-344 | Renders the dashboard's quick-links toolbar. |
| 346-353 | Closes the overview and shared page layout. |

## `staff/components/dashboardNavbar.php`

| Lines | What is there |
| --- | --- |
| 1-23 | Determines the user's role and defines the navigation destinations and the roles allowed to see each link. |
| 25-31 | Chooses desktop primary links and the full set of destinations available in the three-dot menu. |
| 34-49 | Renders the normal dashboard navigation links, active states, icons, and count badges. |
| 51-72 | Renders the three-dot dropdown. On floating dashboard headers, it contains every destination available to the signed-in role; additional desktop links remain in the dropdown as before. |
| 74-75 | Closes the navigation list and element. |

## CSS file: `staff/assets/css/dashboard.css`

This is the dashboard stylesheet. It is loaded by `staff/components/dashboard.php` at line 50. The ranges below describe the CSS used by the dashboard design and the responsive navigation.

| Lines | What the CSS controls |
| --- | --- |
| 1-55 | File conventions, shared dashboard color variables, sizing values, and page reset/base styles. |
| 59-117 | Shared header layout, brand/logo styling, and header controls. |
| 120-160 | Header action spacing and notification button styles. |
| 360-515 | General dashboard navigation and dropdown styles, including link states, count badges, and the base three-dot menu. |
| 4492-4532 | Overview color palette, top-header placement, and announcement treatment. |
| 4534-4670 | Desktop floating navbar layout, link states, active state, role overflow menu, and header actions. |
| 4671-4884 | Overview hero background and layout, introduction typography, glass-style donations panel, and donation chart styling. |
| 4886-5023 | Highlight-card grid, card surfaces, heading/actions, metrics, enquiry segments, and card legends. |
| 5025-5276 | Additional overview visualizations and content: weekly metrics, upcoming-event list, registration honeycomb, and campaign progress bars. |
| 5278-5385 | Quick-links toolbar, notification-control placement, and dashboard content below the hero. |
| 5387-5434 | Intermediate-width dashboard header, navigation spacing, and two-column card adjustments. |
| 5435-5609 | Phone layout. Replaces the crowded horizontal dashboard navigation with the three-dot menu, fits its dropdown to the viewport, and stacks hero content and cards. |
| 5610-5644 | Small-phone card sizing and reduced-motion behavior. |
| 5961-6005 | Shared staff-page background and the solid navy header override used to keep the navbar appearance consistent across dashboard pages. |
| 6052-6056 | Small-screen background-attachment adjustment for the shared staff-page shell. |

`staff/assets/css/dashboard.css` is the only CSS file changed for these dashboard updates. `staff/components/dashboard.php` also loads `assets/css/dashboard.css` at line 50; that stylesheet reference was already present.

## Validation recorded

- PHP syntax checks passed for `adminDashboard.php`, `staffDashboard.php`, and `components/dashboardNavbar.php`.
- `git diff --check` passed for the dashboard changes.
