// about.js runs the our story, mission and vision buttons on about.php

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
