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

            <section class="about-introduction page-hero">
                <div class="container">

                    <div class="row">
                        <div class="col-12 col-lg-9">

                            <!-- Main About Us heading. -->
                            <h1 class="page-hero-title fw-bold">
                                About Us
                            </h1>

                            <!-- introduction. -->
                            <p class="page-hero-description mb-0">
                                Likhwezi Technologies helps organisations use
                                data, technology, and strategic insight to make
                                better business decisions and create lasting value.
                            </p>

                        </div>
                    </div>

                </div>
            </section>

            <!-- divider between sections. --> 
             <hr class="about-divider">

            <!-- Interactive company story, mission, and vision section. -->
            <section class="company-history py-5">
                <div class="container">

                    <!-- Keeps the interactive values on the left and the image on the right. -->
                    <div class="about-history-content">

                        <!-- Lets visitors select which company value to read. -->
                        <section class="about-values" aria-labelledby="aboutValuesTitle">
                            <h2 id="aboutValuesTitle">The values of Likhwezi Technologies</h2>

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
                                    <p class="mb-0">We develop bespoke business solutions tailored to the unique needs of our clients.</p>
                                </div>
                            </div>

                        </section>

                        <!-- Places the About Us image next to the interactive values. -->
                        <div class="about-history-image-wrap">
                            <img
                                src="images/about-us.png"
                                class="img-fluid about-history-image"
                                alt="Likhwezi Technologies team"
                            >
                        </div>
                    </div>

                    <!--divider separating the image from Our Approach. -->
                    <hr class="about-divider about-approach-divider">

                    <!-- Interactive Our Approach values section. -->
                    <section class="our-approach" aria-labelledby="ourApproachTitle">
                        <h2 id="ourApproachTitle">Our Approach</h2>
                        <p class="our-approach-intro">
                            Our approach follows a clear, collaborative process from understanding the challenge
                            through to delivering the end-solution.
                        </p>

                        <!--UADRPRBTB acronym buttons. -->
                        <div class="approach-letters" role="tablist" aria-label="Our approach values">
                            <button type="button" class="approach-letter is-active" role="tab" aria-selected="true" aria-controls="approachDescription" data-approach="understanding">U</button>
                            <button type="button" class="approach-letter" role="tab" aria-selected="false" aria-controls="approachDescription" data-approach="analysing">A</button>
                            <button type="button" class="approach-letter" role="tab" aria-selected="false" aria-controls="approachDescription" data-approach="designing">D</button>
                            <button type="button" class="approach-letter" role="tab" aria-selected="false" aria-controls="approachDescription" data-approach="reviewing-decisions">R</button>
                            <button type="button" class="approach-letter" role="tab" aria-selected="false" aria-controls="approachDescription" data-approach="planning">P</button>
                            <button type="button" class="approach-letter" role="tab" aria-selected="false" aria-controls="approachDescription" data-approach="reviewing-plans">R</button>
                            <button type="button" class="approach-letter" role="tab" aria-selected="false" aria-controls="approachDescription" data-approach="building">B</button>
                            <button type="button" class="approach-letter" role="tab" aria-selected="false" aria-controls="approachDescription" data-approach="testing">T</button>
                            <button type="button" class="approach-letter" role="tab" aria-selected="false" aria-controls="approachDescription" data-approach="delivering">D</button>
                        </div>

                        <!-- selected approach step beneath the letter row. -->
                        <div class="approach-description" id="approachDescription" role="tabpanel">
                            <p class="approach-description-title" id="approachDescriptionTitle">Understanding</p>
                            <p id="approachDescriptionText" class="mb-0">Understanding the business challenge</p>
                        </div>
                    </section>

                </div>
            </section>

            <!--divider between sections. --> 
             <hr class="about-divider">

            <!-- Meet Our Team section. -->
            <section class="meet-team py-5">
                <div class="container">

                    <!-- Team section heading and introduction. -->
                    <div class="row justify-content-center text-center mb-5">
                        <div class="col-12 col-lg-8">

                            <!-- Team section heading. -->
                            <h2 class="h2 mb-3">
                                Meet Our Team
                            </h2>

                            <!--team introduction. -->
                            <p class="mb-0">
                                Meet the people who contribute their knowledge,
                                experience, and commitment to the work we do.
                            </p>

                        </div>
                    </div>

                    <!-- First row containing three team member modules. -->
                    <div class="row g-4 mb-4">

                        <!-- Team member slot one. -->
                        <div class="col-12 col-md-4">
                            <article class="team-member-card h-100">

                                <!--portrait placeholder. -->
                                <img
                                    src="images/placeholder.webp"
                                    class="team-member-image"
                                    alt="Placeholder portrait for team member one"
                                >

                                <!-- Contains team member one information. -->
                                <div class="card-body">

                                    <!-- Temporary team member name. -->
                                    <h3 class="h5">
                                        Team Member One
                                    </h3>

                                    <!-- Temporary team member role. -->
                                    <p class="team-member-role">
                                        Executive Director
                                    </p>

                                    <!-- Temporary team member description. -->
                                    <p class="card-text">
                                        Placeholder description for the first
                                        member of the Likhwezi Technologies team.
                                    </p>

                                </div>

                            </article>
                        </div>

                        <!-- Team member slot two. -->
                        <div class="col-12 col-md-4">
                            <article class="team-member-card h-100">

                                <!-- Uses the temporary placeholder portrait. -->
                                <img
                                    src="images/placeholder.webp"
                                    class="team-member-image"
                                    alt="Placeholder portrait for team member two"
                                >

                                <!-- Contains team member two information. -->
                                <div class="card-body">

                                    <!-- Temporary team member name. -->
                                    <h3 class="h5">
                                        Team Member Two
                                    </h3>

                                    <!-- Temporary team member role. -->
                                    <p class="team-member-role">
                                        Enterprise Architect
                                    </p>

                                    <!-- Temporary team member description. -->
                                    <p class="card-text">
                                        Placeholder description for the second
                                        member of the Likhwezi Technologies team.
                                    </p>

                                </div>

                            </article>
                        </div>

                        <!-- Team member slot three. -->
                        <div class="col-12 col-md-4">
                            <article class="team-member-card h-100">

                                <!-- Uses the temporary placeholder portrait. -->
                                <img
                                    src="images/placeholder.webp"
                                    class="team-member-image"
                                    alt="Placeholder portrait for team member three"
                                >

                                <!-- Contains team member three information. -->
                                <div class="card-body">

                                    <!-- Temporary team member name. -->
                                    <h3 class="h5">
                                        Team Member Three
                                    </h3>

                                    <!-- Temporary team member role. -->
                                    <p class="team-member-role">
                                        Data Management Specialist
                                    </p>

                                    <!-- Temporary team member description. -->
                                    <p class="card-text">
                                        Placeholder description for the third
                                        member of the Likhwezi Technologies team.
                                    </p>

                                </div>

                            </article>
                        </div>

                    </div>

                    <!-- Second row containing three team member modules. -->
                    <div class="row g-4">

                        <!-- Team member slot four. -->
                        <div class="col-12 col-md-4">
                            <article class="team-member-card h-100">

                                <!-- Uses the temporary placeholder portrait. -->
                                <img
                                    src="images/placeholder.webp"
                                    class="team-member-image"
                                    alt="Placeholder portrait for team member four"
                                >

                                <!-- Contains team member four information. -->
                                <div class="card-body">

                                    <!-- Temporary team member name. -->
                                    <h3 class="h5">
                                        Team Member Four
                                    </h3>

                                    <!-- Temporary team member role. -->
                                    <p class="team-member-role">
                                        Strategic Adviser
                                    </p>

                                    <!-- Temporary team member description. -->
                                    <p class="card-text">
                                        Placeholder description for the fourth
                                        member of the Likhwezi Technologies team.
                                    </p>

                                </div>

                            </article>
                        </div>

                        <!-- Team member slot five. -->
                        <div class="col-12 col-md-4">
                            <article class="team-member-card h-100">

                                <!-- Uses the temporary placeholder portrait. -->
                                <img
                                    src="images/placeholder.webp"
                                    class="team-member-image"
                                    alt="Placeholder portrait for team member five"
                                >

                                <!-- Contains team member five information. -->
                                <div class="card-body">

                                    <!-- Temporary team member name. -->
                                    <h3 class="h5">
                                        Team Member Five
                                    </h3>

                                    <!-- Temporary team member role. -->
                                    <p class="team-member-role">
                                        Project Consultant
                                    </p>

                                    <!-- Temporary team member description. -->
                                    <p class="card-text">
                                        Placeholder description for the fifth
                                        member of the Likhwezi Technologies team.
                                    </p>

                                </div>

                            </article>
                        </div>

                        <!-- Team member slot six. -->
                        <div class="col-12 col-md-4">
                            <article class="team-member-card h-100">

                                <!-- Uses the temporary placeholder portrait. -->
                                <img
                                    src="images/placeholder.webp"
                                    class="team-member-image"
                                    alt="Placeholder portrait for team member six"
                                >

                                <!-- Contains team member six information. -->
                                <div class="card-body">

                                    <!-- Temporary team member name. -->
                                    <h3 class="h5">
                                        Team Member Six
                                    </h3>

                                    <!-- Temporary team member role. -->
                                    <p class="team-member-role">
                                        Community Programmes Lead
                                    </p>

                                    <!-- Temporary team member description. -->
                                    <p class="card-text">
                                        Placeholder description for the sixth
                                        member of the Likhwezi Technologies team.
                                    </p>

                                </div>

                            </article>
                        </div>

                    </div>

                </div>
            </section>

        </main>

        <?php
        // Loads the shared footer and Bootstrap JavaScript bundle.
        include 'components/footer.php';
        ?>

        <script>
            // Stores the meaning and description for each approach step.
            const approachDetails = {
                understanding: {
                    title: 'Understanding',
                    description: 'Understanding the business challenge'
                },
                analysing: {
                    title: 'Analysing',
                    description: 'Analysing the business and technical environment(s)'
                },
                designing: {
                    title: 'Designing',
                    description: 'Designing a fit-for-purpose solution'
                },
                'reviewing-decisions': {
                    title: 'Reviewing',
                    description: 'Reviewing solution decisions with business partners'
                },
                planning: {
                    title: 'Planning',
                    description: 'Planning solution delivery'
                },
                'reviewing-plans': {
                    title: 'Reviewing',
                    description: 'Reviewing those delivery plan(s)'
                },
                building: {
                    title: 'Building',
                    description: 'Building tailored solutions'
                },
                testing: {
                    title: 'Testing',
                    description: 'Testing the solution'
                },
                delivering: {
                    title: 'Delivering',
                    description: 'Delivering the end-solution'
                }
            };

            // Gets the approach controls and the description elements.
            const approachLetters = document.querySelectorAll('.approach-letter');
            const approachDescriptionTitle = document.getElementById('approachDescriptionTitle');
            const approachDescriptionText = document.getElementById('approachDescriptionText');

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

            // Updates the description when a visitor selects an approach step.
            approachLetters.forEach((letter) => {
                letter.addEventListener('click', () => {
                    const selectedApproach = approachDetails[letter.dataset.approach];

                    // Updates the selected state and accessible tab status.
                    approachLetters.forEach((item) => {
                        item.classList.remove('is-active');
                        item.setAttribute('aria-selected', 'false');
                    });
                    letter.classList.add('is-active');
                    letter.setAttribute('aria-selected', 'true');

                    // Displays the selected approach meaning below the acronym row.
                    approachDescriptionTitle.textContent = selectedApproach.title;
                    approachDescriptionText.textContent = selectedApproach.description;
                });
            });
        </script>

    </body>
</html>
