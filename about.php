<?php
session_start();
include 'tools/dbConnection.php';
$pageTitle = 'About Us';
?>

<!DOCTYPE html>
<html lang="en">

    <?php include 'components/header.php';?>

    <body>

        <?php include 'components/navBar.php';?>

        <main class="about-page">

            <!-- hero section -->
            <section class="hero hero-text-only">
                <div class="hero-content d-grid gap-3 row-gap-3">
                    <div class="p-1">
                        <h1>About Us</h1>
                        <p>Likhwezi Technologies helps organisations use data, technology, and strategic insight to make better business decisions and create lasting value.</p>
                    </div>
                </div>
            </section>

            <hr>

            <!-- Interactive company story, mission, and vision section. -->
            <section class="page-section company-history" aria-labelledby="aboutValuesTitle">

                <h2 class="section-title" id="aboutValuesTitle">The values of Likhwezi Technologies</h2>

                <!-- Keeps the interactive values on the left and the image on the right. -->
                <div class="about-history-content">

                    <!-- Lets visitors select which company value to read. -->
                    <div class="about-values">

                        <!-- Uses buttons so the section can be operated with a keyboard. -->
                        <div class="about-value-controls" role="tablist" aria-label="Company information">
                            <button type="button" class="about-value-button is-active" role="tab" aria-selected="true" aria-controls="aboutValueDescription" data-about-value="story">Our Story</button>
                            <button type="button" class="about-value-button" role="tab" aria-selected="false" aria-controls="aboutValueDescription" data-about-value="mission">Mission</button>
                            <button type="button" class="about-value-button" role="tab" aria-selected="false" aria-controls="aboutValueDescription" data-about-value="vision">Vision</button>
                        </div>

                        <!-- Displays the selected company information below the buttons. -->
                        <div class="about-value-description" id="aboutValueDescription" role="tabpanel" tabindex="0">
                            <p class="about-value-description-title" id="aboutValueDescriptionTitle">Our Story</p>
                            <div id="aboutValueDescriptionText">
                                <p>Likhwezi Consulting is a 100% black-owned professional services consultancy with a focus on enterprise data systems, and related methods and practices.</p>
                                <p>We develop bespoke business solutions tailored to the unique needs of our clients.</p>
                            </div>
                        </div>

                    </div>

                    <!-- Places the About Us image next to the interactive values. -->
                    <div class="about-history-image-wrap">
                        <img
                            src="images/about-us.png"
                            class="about-history-image"
                            alt="Likhwezi Technologies team"
                        >
                    </div>
                </div>

            </section>

            <hr>

            <!-- Meet Our Team section. -->
            <section class="page-section meet-team" aria-labelledby="meetTeamTitle">

                <h2 class="section-title" id="meetTeamTitle">Meet Our Team</h2>
                <p class="section-intro">
                    Meet the people who contribute their knowledge, experience, and commitment to the work we do.
                </p>

                <!-- Three columns on desktop, two on tablets, one on phones. -->
                <div class="team-grid">

                    <!-- Team member slot one. -->
                    <article class="team-member-card">
                        <img
                            src="images/placeholder.webp"
                            class="team-member-image"
                            alt="Placeholder portrait for team member one"
                        >
                        <div class="team-member-body">
                            <h3>Team Member One</h3>
                            <p class="team-member-role">Executive Director</p>
                            <p class="team-member-bio">Placeholder description for the first member of the Likhwezi Technologies team.</p>
                        </div>
                    </article>

                    <!-- Team member slot two. -->
                    <article class="team-member-card">
                        <img
                            src="images/placeholder.webp"
                            class="team-member-image"
                            alt="Placeholder portrait for team member two"
                        >
                        <div class="team-member-body">
                            <h3>Team Member Two</h3>
                            <p class="team-member-role">Enterprise Architect</p>
                            <p class="team-member-bio">Placeholder description for the second member of the Likhwezi Technologies team.</p>
                        </div>
                    </article>

                    <!-- Team member slot three. -->
                    <article class="team-member-card">
                        <img
                            src="images/placeholder.webp"
                            class="team-member-image"
                            alt="Placeholder portrait for team member three"
                        >
                        <div class="team-member-body">
                            <h3>Team Member Three</h3>
                            <p class="team-member-role">Data Management Specialist</p>
                            <p class="team-member-bio">Placeholder description for the third member of the Likhwezi Technologies team.</p>
                        </div>
                    </article>

                    <!-- Team member slot four. -->
                    <article class="team-member-card">
                        <img
                            src="images/placeholder.webp"
                            class="team-member-image"
                            alt="Placeholder portrait for team member four"
                        >
                        <div class="team-member-body">
                            <h3>Team Member Four</h3>
                            <p class="team-member-role">Strategic Adviser</p>
                            <p class="team-member-bio">Placeholder description for the fourth member of the Likhwezi Technologies team.</p>
                        </div>
                    </article>

                    <!-- Team member slot five. -->
                    <article class="team-member-card">
                        <img
                            src="images/placeholder.webp"
                            class="team-member-image"
                            alt="Placeholder portrait for team member five"
                        >
                        <div class="team-member-body">
                            <h3>Team Member Five</h3>
                            <p class="team-member-role">Project Consultant</p>
                            <p class="team-member-bio">Placeholder description for the fifth member of the Likhwezi Technologies team.</p>
                        </div>
                    </article>

                    <!-- Team member slot six. -->
                    <article class="team-member-card">
                        <img
                            src="images/placeholder.webp"
                            class="team-member-image"
                            alt="Placeholder portrait for team member six"
                        >
                        <div class="team-member-body">
                            <h3>Team Member Six</h3>
                            <p class="team-member-role">Community Programmes Lead</p>
                            <p class="team-member-bio">Placeholder description for the sixth member of the Likhwezi Technologies team.</p>
                        </div>
                    </article>

                </div>

            </section>

        </main>

        <?php
        // Loads the shared footer and Bootstrap JavaScript bundle.
        include 'components/footer.php';
        ?>

        <script>
            // Stores the descriptions for the interactive story, mission, and vision section.
            const aboutValueDetails = {
                story: {
                    title: 'Our Story',
                    description: [
                        'Likhwezi Consulting is a 100% black-owned professional services consultancy with a focus on enterprise data systems, and related methods and practices.',
                        'We develop bespoke business solutions tailored to the unique needs of our clients.'
                    ]
                },
                mission: {
                    title: 'Mission',
                    description: [
                        'Our mission is to help private and public sector organisations realise and deliver business value through data insights.'
                    ]
                },
                vision: {
                    title: 'Vision',
                    description: [
                        'Develop Likhwezi Consulting as a global market leader in the design, development, and delivery of business and data management systems.',
                        'To be a trusted advisor and partner of choice for businesses in the private and public sectors.',
                        'Ensure that business and data management education is accessible to the youth of Africa and use it to alleviate skills shortages and youth unemployment.'
                    ]
                }
            };

            // Gets the company value controls and description elements.
            const aboutValueButtons = document.querySelectorAll('.about-value-button');
            const aboutValueDescriptionTitle = document.getElementById('aboutValueDescriptionTitle');
            const aboutValueDescriptionText = document.getElementById('aboutValueDescriptionText');

            // Updates the description when a visitor selects a company value.
            aboutValueButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const selectedValue = aboutValueDetails[button.dataset.aboutValue];

                    // Updates the selected state and accessible tab status.
                    aboutValueButtons.forEach((item) => {
                        item.classList.remove('is-active');
                        item.setAttribute('aria-selected', 'false');
                    });
                    button.classList.add('is-active');
                    button.setAttribute('aria-selected', 'true');

                    // Displays each paragraph belonging to the selected company value.
                    aboutValueDescriptionTitle.textContent = selectedValue.title;
                    aboutValueDescriptionText.innerHTML = selectedValue.description
                        .map((paragraph) => `<p>${paragraph}</p>`)
                        .join('');
                });
            });
        </script>

    </body>
</html>
