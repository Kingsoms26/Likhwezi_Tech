<?php
    // about.php is the about us page
    // contains the hero section, our story mission and vision section and the meet our team section
    session_start();
    $pageTitle = 'About Us';
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

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

            <!-- our story, mission and vision section -->
            <section class="page-section company-history" aria-labelledby="aboutValuesTitle">

                <h2 class="section-title" id="aboutValuesTitle">Our Story, Mission and Vision</h2>

                <!-- values on the left and the image on the right -->
                <div class="about-history-content">

                    <!-- visitors pick which one to read -->
                    <div class="about-values">

                        <!-- buttons so it works with a keyboard -->
                        <div class="about-value-controls" role="tablist" aria-label="Company information">
                            <button type="button" class="about-value-button is-active" role="tab" aria-selected="true" aria-controls="aboutValueDescription" data-about-value="story">Our Story</button>
                            <button type="button" class="about-value-button" role="tab" aria-selected="false" aria-controls="aboutValueDescription" data-about-value="mission">Mission</button>
                            <button type="button" class="about-value-button" role="tab" aria-selected="false" aria-controls="aboutValueDescription" data-about-value="vision">Vision</button>
                        </div>

                        <!-- the chosen one shows below the buttons -->
                        <div class="about-value-description" id="aboutValueDescription" role="tabpanel" tabindex="0">
                            <p class="about-value-description-title" id="aboutValueDescriptionTitle">Our Story</p>
                            <div id="aboutValueDescriptionText">
                                <p>Likhwezi Technologies is a 100% black-owned professional services consultancy with a focus on enterprise data systems, and related methods and practices.</p>
                                <p>We develop bespoke business solutions tailored to the unique needs of our clients.</p>
                            </div>
                        </div>

                    </div>

                    <!-- about us image -->
                    <div class="about-history-image-wrap">
                        <img src="assets/images/about-img/about-us.webp" class="about-history-image" width="938" height="602" alt="Likhwezi Technologies team">
                    </div>
                </div>

            </section>

            <hr>

            <!-- meet our team section -->
            <section class="page-section meet-team" aria-labelledby="meetTeamTitle">

                <h2 class="section-title" id="meetTeamTitle">Meet Our Team</h2>
                <p class="section-intro">Meet the people who contribute their knowledge, experience, and commitment to the work we do.</p>

                <!-- three columns on desktop, two on tablets, one on phones -->
                <div class="team-grid">

                    <!-- team member one -->
                    <article class="team-member-card">
                        <img src="assets/images/about-img/lindokuhle-qhankqashe.webp" class="team-member-image" loading="lazy" alt="Placeholder portrait for team member one">
                        <div class="team-member-body">
                            <h3>Lindokuhle Qhankqashe</h3>
                        </div>
                    </article>

                    <!-- team member two -->
                    <article class="team-member-card">
                        <img src="assets/images/about-img/lwazi-mqingwana.webp" class="team-member-image" loading="lazy" alt="Placeholder portrait for team member two">
                        <div class="team-member-body">
                            <h3>Lwazi Mqingwana</h3>
                        </div>
                    </article>

                    <!-- team member three -->
                    <article class="team-member-card">
                        <img src="assets/images/about-img/neliswa-chopela.webp" class="team-member-image" loading="lazy" alt="Placeholder portrait for team member three">
                        <div class="team-member-body">
                            <h3>Neliswa Chopela</h3>
                        </div>
                    </article>

                    <!-- team member four -->
                    <article class="team-member-card">
                        <img src="assets/images/about-img/m-jay-pingo.webp" class="team-member-image" loading="lazy" alt="Placeholder portrait for team member four">
                        <div class="team-member-body">
                            <h3>M-Jay Pingo</h3>
                        </div>
                    </article>

                    <!-- team member five -->
                    <article class="team-member-card">
                        <img src="assets/images/about-img/pamela-ngwenya.webp" class="team-member-image" loading="lazy" alt="Placeholder portrait for team member five">
                        <div class="team-member-body">
                            <h3>Pamela Ngwenya</h3>
                        </div>
                    </article>

                    <!-- team member six -->
                    <article class="team-member-card">
                        <img src="assets/images/placeholder.webp" class="team-member-image" width="800" height="800" loading="lazy" alt="Placeholder portrait for team member six">
                        <div class="team-member-body">
                            <h3>Lulekwa Mcwabeni</h3>
                        </div>
                    </article>

                </div>

            </section>

        </main>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>

        <script>
            // wording for our story, mission and vision
            const aboutValueDetails = {
                story: {
                    title: 'Our Story',
                    description: [
                        'Likhwezi Technologies is a 100% black-owned professional services consultancy with a focus on enterprise data systems, and related methods and practices.',
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
                        'Develop Likhwezi Technologies as a global market leader in the design, development, and delivery of business and data management systems.',
                        'To be a trusted advisor and partner of choice for businesses in the private and public sectors.',
                        'Ensure that business and data management education is accessible to the youth of Africa and use it to alleviate skills shortages and youth unemployment.'
                    ]
                }
            };

            // the buttons and where the wording shows
            const aboutValueButtons = document.querySelectorAll('.about-value-button');
            const aboutValueDescriptionTitle = document.getElementById('aboutValueDescriptionTitle');
            const aboutValueDescriptionText = document.getElementById('aboutValueDescriptionText');

            // show the wording for the button that was clicked
            aboutValueButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const selectedValue = aboutValueDetails[button.dataset.aboutValue];

                    // mark the clicked button as selected
                    aboutValueButtons.forEach((item) => {
                        item.classList.remove('is-active');
                        item.setAttribute('aria-selected', 'false');
                    });
                    button.classList.add('is-active');
                    button.setAttribute('aria-selected', 'true');

                    // show each paragraph for the chosen one
                    aboutValueDescriptionTitle.textContent = selectedValue.title;
                    aboutValueDescriptionText.innerHTML = selectedValue.description
                        .map((paragraph) => `<p>${paragraph}</p>`)
                        .join('');
                });
            });
        </script>
    </body>
</html>
