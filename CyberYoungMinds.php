<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Likhwezi | Cyber Young Minds";
$navTitle = "Cyber Young Minds";
$navSubtitle = "by Likhwezi Technologies";
$pageLogo = "images/WhatsApp Image 2026-09-02 at 19.19.46.jpeg";
$pageLogoAlt = "Cyber Young Minds logo";

function normalise_phone(string $input): ?string {
    $d = preg_replace('/\D/', '', $input);
    if (str_starts_with($d, '27')) $d = '0' . substr($d, 2);
    if (str_starts_with($d, '0027')) $d = '0' . substr($d, 4);
    return preg_match('/^0[1-8]\d{8}$/', $d) ? $d : null;
}

?>
<!DOCTYPE html>
<html lang="en">
<?php include 'components/header.php'; ?>
<style>
    
body.cym-page{
    font-family: Arial, Helvetica, sans-serif;
    line-height: 1.5rem;
    margin: 0;
}

body.cym-page .navbar {
    background-color: #0389E1;
}

body.cym-page .navbar .nav-link,
body.cym-page .navbar .company-name {
    color: white;
}

body.cym-page .navbar .company-name {
    font-size: 0.9rem;
    line-height: 1.1;
}

body.cym-page .logo-title {
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

body.cym-page .logo-sub {
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.72rem;
    margin: 0;
    line-height: 1.2;
}

/* Hero section*/
.cym-hero{
    background-color:#0389E1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
    padding: 3rem var(--section-gutter) 6rem;
    position: relative;
    clip-path: polygon(0 0, 70% 0, 45% 100%, 0 100%);
}

.hero-text{
    color: white;
    max-width: 480px;
}

.hero-text p{
    color: white;
    font-size: 1.125rem;
}

.hero-carousel{
    width: 300px;
    height: 200px;
    flex-shrink: 0;
}

.btn-cymRegister{
    padding: 8px 16px;
    margin: 0;
    background-color: white;
    color:#0F3C7A;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    align-items:end;
}

.btn-cym{
    padding: 8px 16px;
    background-color:#0F3C7A;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
}

button.btn-cym:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.banner{
    overflow: hidden;
    background-color: white;
    color: rgb(3, 3, 42);
    white-space: nowrap;
}

.banner-track{
    display: flex;
    align-items: center;
    width: max-content;
    min-width: 100%;
    padding: 0.8rem 0;
    will-change: transform;
    animation: banner-scroll 20s linear infinite;
}

.banner-group{
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.banner p{
    flex: 0 0 auto;
    width: max-content;
    margin: 0;
    white-space: nowrap;
    letter-spacing: 0.02em;
    padding-right: 2.5rem;
}

.banner-group:last-child p:last-child {
    padding-right: 0;
}

@keyframes banner-scroll{
    from {
        transform: translate3d(0, 0, 0);
    }
    to {
        transform: translate3d(-50%, 0, 0);
    }
}

.cym-overview{
    background-color: #0389E1;
    padding: 3rem var(--section-gutter);
    color: white;
}

.cym-info{
    align-items: flex-start;
}

.overview-row{
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 0.8fr);
    gap: 3rem;
    align-items: center;
}

.overview-intro{
    margin-bottom: 2rem;
}

.overview-registration{
    margin-top: 2rem;
}

.cym-info p{
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.125rem;

}

.cym-stats{
    display: grid;
    border-radius: 1rem;
    font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
    color: white;
    margin: 1.5rem 0;
}

.cym-stats .stat-row{
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    padding: 10px 0;
    gap: 1rem;
    margin: 0;
}

.cym-stats h6{
    font-size: 2rem;
    margin: 0;
    font-weight: 400;
    color: white;
}

.cym-buttons{
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
    margin-top: 1rem;
}

.cym-buttons .btn-cymRegister{
    margin: 0;
}

/* The light info card */
.info-card{
    background: #FFFFFF2B 17%;
    border-radius: 1rem;
    padding: 1.5rem;
    color: white;
}

.info-card ul{
    padding: 0;
    margin: 0.5rem 0 0 0;
}

.info-card li{
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 5px;
}

@media screen and (max-width: 768px){
    .cym-overview{
        padding: 2rem var(--section-gutter);
    }

    .overview-row{
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .overview-intro,
    .overview-registration{
        margin-bottom: 1.5rem;
    }
}

@media (prefers-reduced-motion: reduce){
    .banner-track{
        animation: none;
    }
}

.link{
    color: #2F1E4B;
    font-weight: 600;
}

/*Registration form*/
.cym-register {
    padding: 2rem;
    border-radius: 20px;
    width: 100%;
    box-sizing: border-box;
}
.cym-register h2 { 
    color: rgb(3, 19, 60); 
    font-size: 1.6rem; 
    margin-bottom: 0.3rem; 
}
.cym-register .subtitle { 
    color: rgb(3, 19, 60); 
    font-size: 0.85rem; 
    margin-bottom: 1.8rem; 
}
.cym-register label { 
    display: block; 
    font-weight: 600; 
    font-size: 0.82rem; 
    color:rgb(3, 19, 60); 
    margin-bottom: 5px; 
    margin-top: 1rem; 
}
.cym-register input { 
    width: 100%; 
    box-sizing: border-box;
    padding: 11px 14px; 
    border: 1.5px solid white; 
    border-radius: 10px; 
    font-family: inherit; 
    font-size: 0.9rem; 
    background:White; 
    color:rgb(3, 19, 60);
    outline: grey; 
    transition: 0.2s; 
}

.cym-register input:focus{
    background: white;
    color: rgb(3, 19, 60);
    border-color: #0389E1;
    box-shadow: 0 0 0 3px rgba(3, 137, 225, 0.2);
}

.cym-register input::placeholder{
    color: rgb(3, 19, 60);
    opacity: 0.4make;
}

.cym-register input:focus::placeholder{
    color: rgb(3, 19, 60);
    opacity: 0.4;
}

.cym-register select{
    width: 100%;
    box-sizing: border-box;
    padding: 11px 14px;
    border: 1.5px solid white;
    border-radius: 10px;
    font: inherit;
    background: white;
}

.form-row{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-field{
    min-width: 0;
}

.guardian-fields{
    display: grid;
    gap: 0.25rem;
    margin-top: 0.75rem;
}

.cym-register .consent{
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    margin: 1rem 0 0;
    line-height: 1.4;
}

.cym-register .consent input[type="checkbox"]{
    width: auto;
    flex: 0 0 auto;
    margin-top: 0.2rem;
}

.guardian-fields[hidden]{
    display: none;
}

@media screen and (max-width: 560px){
    .form-row{
        grid-template-columns: 1fr;
    }
}
 
/* Registration modal styles */
.modal-overlay{
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(3, 3, 42, 0.55);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-overlay.active{
    display: flex;
}

body.modal-open{
    overflow: hidden;
}

.modal-box{
    background: white;
    border-radius: 1rem;
    padding: 1rem;
    width: 90%;
    max-width: 620px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    color: rgb(3, 3, 42);
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}

/* Privacy modal styles */
.privacy-modal-box,
.success-modal-box {
    max-width: 700px;
}

.privacy-content,
.success-content {
    color: rgb(3, 3, 42);
    line-height: 1.6;
    font-size: 0.95rem;
}

.privacy-content p,
.success-content p {
    margin: 0 0 0.9rem;
}

.privacy-modal-actions,
.success-modal-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 1rem;
}

.modal-close{
    position: absolute;
    top: 12px;
    right: 16px;
    background: none;
    border: none;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    color: rgb(3, 3, 42);
}

.modal-box h3{
    margin-top: 0;
}

.modal-box form{
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1rem;
}

.modal-box label{
    font-size: 0.85rem;
    font-weight: 600;
}

.modal-box input,
.modal-box select{
    box-sizing: border-box;
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    margin-bottom: 0.6rem;
}

.modal-box button[type="submit"]{
    border: none;
    cursor: pointer;
    margin-top: 0.5rem;
}

@media screen and (max-width: 560px){
    .modal-box{
        width: 94%;
        padding: 0.5rem;
    }

    .cym-register{
        padding: 1.5rem;
    }
}
</style>
<body class="cym-page">
    <?php include 'components/navBar.php'; ?>

    <section class="cym-hero">
        <div class="hero-text">
<h1 class="slogan">Building the minds that build the future</h1>
<p>Coding, Robotics, and AI,taught by the people who do it for a living. No experience needed, no laptop needed, nothing to pay</p>
    <a href="#modal-overlay" class="btn-cymRegister register-btn">Register</a>
      </div>
      <div class="hero-carousel">
        
      </div>
</section>

<section class="banner" aria-label="Coding and technology programmes banner">
    <div class="banner-track" aria-label="Coding, Artificial Intelligence, Robotics and Hackathons">
        <div class="banner-group">
            <p>Coding | Artificial Intelligence | Robotics | Hackathons</p>
            <p>Coding | Artificial Intelligence | Robotics | Hackathons</p>
            <p>Coding | Artificial Intelligence | Robotics | Hackathons</p>
        </div>
        <div class="banner-group" aria-hidden="true">
            <p>Coding | Artificial Intelligence | Robotics | Hackathons</p>
            <p>Coding | Artificial Intelligence | Robotics | Hackathons</p>
            <p>Coding | Artificial Intelligence | Robotics | Hackathons</p>
        </div>
    </div>
</section>

<section class="cym-overview" id="overview">
    <div class="overview-row overview-intro">
        <div class="cym-info">
            <h3>Most Young people meet technology as something that happens to them. We think they should meet it as something they can take apart and participate in</h3>
            <p>Cyber Young Minds runs programme catered to the youth, with programmees in coding, robotics, and AI for learners, their teachers, and anyone in the community who wants to start, It's all funded by Likhwezi Technologies' consultancy work and by sponsors, which is why it costs partcipants nothing</p>
        </div>
        <div class="cym-stats">
            <div class="stat-row"><h6>4</h6><span>Programmes to participate in</span></div>
            <div class="stat-row"><h6>255</h6><span>Registered participants</span></div>
            <div class="stat-row"><h6>R0</h6><span>Charged to participants</span></div>
            <div class="cym-buttons">
                <a href="" class="btn-cym">Go to Cyber Young Minds</a>
                <a href="#modal-overlay" class="btn-cymRegister register-btn">Register</a>
            </div>
        </div>
    </div>
<hr style="border: 1px solid white; margin: 2rem 0;">
    <div class="overview-row overview-registration">
        <div class="cym-info">
                <h3>Register for program or event</h3>
                <p>The programme runs its own site, with the full calendar, venues and the registration form. It takes only two minutes and there's nothing to pay</p>
        </div>

        <div class="info-card">
            <p class="link">cyberyoungminds.co.za</p>
            <h6>What you'll find there</h6>
            <ul>
                <li>Registrations for all programmes</li>
                <li>Session times and dates</li>
                <li>Photos and recaps of previous sessions</li>
                <li>Contact Details</li>
            </ul>
        </div>
    </div>

</section>

<div class="modal-overlay" id="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="register-title">
    <div class="modal-box" id="register">
        <button type="button" class="modal-close" id="modal-close" aria-label="Close registration form">&times;</button>
<div class="cym-register">
        <h3 id="register-title">Register for Cyber Young Minds</h3>
        <p class="subtitle">Fill in your details and we'll be in touch with session times and venue info.</p>
        <form id="register-form">
        <div class="form-row">
            <div class="form-field">
                <label for="register-fname">Full Name</label>
                <input type="text" id="register-fname" name="name" placeholder="Enter your full name as provided on ID" required>
            </div>
            <div class="form-field">
                <label for="register-lname">Last Name</label>
                <input type="text" id="register-lname" name="lastName" placeholder="Enter your last name (Surname)" required>
            </div>
        </div>


        <div class="form-row">
            <div class="form-field">
                <label for="register-age">Age</label>
                <input type="number" id="register-age" name="age" placeholder="Enter your age" min="0" required>
            </div>
            <div class="form-field">
                <label for="reg-email">Email <span class="field-note">(optional if phone is provided)</span></label>
                <input type="email" id="reg-email" name="email" placeholder="you@email.com">
            </div>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="phone">Phone number <span class="field-note">(optional if email is provided)</span></label>
                <input type="tel" id="phone" name="phoneNumber" placeholder="012 345 6789" maxlength="12" inputmode="numeric">
            </div>
            <div class="form-field">
                <label for="register-program">Programme</label>
                <select id="register-program" name="program" required>
                    <option value="">Select Programme</option>
                    <option value="Coding">Coding</option>
                    <option value="Artificial Intelligence">Artificial Intelligence</option>
                    <option value="Robotics">Robotics</option>
                    <option value="Hackathons">Hackathons</option>
                </select>
            </div>
        </div>

            <label class="consent">
                <input type="checkbox" name="consent-main" required>
                <span>I have read and understood the <a href="#" class="privacy-link-trigger">Privacy Notice and Consent</a> to Likhwezi Technologies collecting and processing my personal information in accordance with POPIA and for the purposes described above.
</span>
            </label>

                 <label class="consent">
                <input type="checkbox" name="media-consent" >
                     <span><strong>Media consent (optional):</strong> I consent to Likhwezi Technologies using photographs, videos, testimonials, and recordings taken during programmes, training sessions, events, or activities for promotional, educational, reporting, and marketing purposes.</span>
            </label>

        <div class="guardian-fields" id="guardian-fields" hidden>
            <h4>Guardian details are required for participants under 18.</h4>
            <div class="form-row">
                <div class="form-field">
                    <label for="guardian-fname">Guardian Full Name</label>
                    <input type="text" id="guardian-fname" name="guardianName" placeholder="Guardian full name">
                </div>
                <div class="form-field">
                    <label for="guardian-lname">Guardian Last Name</label>
                    <input type="text" id="guardian-lname" name="guardianLastName" placeholder="Guardian surname">
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label for="register-guardRela">Relationship with Guardian</label>
                    <input type="text" id="register-guardRela" name="relationship" placeholder="e.g. Parent, Aunt, Uncle, Grandparent">
                </div>
                <div class="form-field">
                    <label for="phone">Phone number</label>
                <input type="tel" id="phone" name="phoneNumber" placeholder="012 345 6789" maxlength="12" inputmode="numeric">
                </div>
            </div>

            <label class="consent">
                <input type="checkbox" name="guardian-consent" required>
                <span>I have read and understood the <a href="privacy-notice.html">Privacy Notice and Consent</a> to Likhwezi Technologies collecting and processing my personal information in accordance with POPIA and for the purposes described above.
</span>
            </label>

            <label class="consent">
                <input type="checkbox" name="guardianConsent">
                <span>I, as the guardian of the registered participant, consent to my information being used for the purpose of registration and communication.</span>
            </label>
        </div>

            <button type="submit" class="btn-cym" disabled>Submit</button>
        </form>
        </div>
</div>
</div>
                    <!--Consent Modal-->
<div class="modal-overlay" id="privacy-modal" role="dialog" aria-modal="true" aria-labelledby="privacy-title">
    <div class="modal-box privacy-modal-box">
        <button type="button" class="modal-close privacy-modal-close" aria-label="Close privacy notice">&times;</button>
        <h3 id="privacy-title">Privacy Notice and Consent</h3>
        <div class="privacy-content">
            <p><strong>Consent to Collection and Processing of Personal Information</strong></p>
            <p>By using this website, submitting any form, registering for a programme, event, training course, or engaging with Likhwezi Technologies (Pty) Ltd, you consent to the collection, storage, processing, and use of your personal information for lawful business purposes.</p>
            <p>Likhwezi Technologies may collect information such as your name, contact details, identification number, educational background, employment information, and other details necessary to provide services, training, support, communications, and programme administration.</p>
            <p>We undertake to process your personal information in accordance with the Protection of Personal Information Act, 2013 (POPIA) and other applicable laws. Your information will only be used for the purpose for which it was collected and may be shared with authorized partners, sponsors, accreditation bodies, employers, or service providers where necessary to deliver services or comply with legal obligations.</p>
            <p>By submitting your information, you further consent to receiving communications from Likhwezi Technologies regarding programmes, events, opportunities, updates, and services. You may opt out of marketing communications at any time.</p>
            <p>You have the right to access, update, correct, or request the deletion of your personal information, subject to applicable legal and contractual requirements.</p>
            <p>If you do not agree to the terms of this consent, please refrain from submitting your personal information through this website.</p>
        </div>
        <div class="privacy-modal-actions">
            <button type="button" class="btn-cym privacy-modal-close-btn">I understand</button>
        </div>
    </div>
</div>
<!--Success Modal-->
<div class="modal-overlay" id="success-modal" role="dialog" aria-modal="true" aria-labelledby="success-title">
    <div class="modal-box success-modal-box">
        <button type="button" class="modal-close success-modal-close" aria-label="Close success message">&times;</button>
        <h3 id="success-title">Registration received</h3>
        <div class="success-content">
            <p>Thank you for registering for Cyber Young Minds.</p>
            <p>We have received your details and will contact you with the next steps, dates, and venue information.</p>
        </div>
        <div class="success-modal-actions">
            <button type="button" class="btn-cym success-close-btn">Close</button>
        </div>
    </div>
</div>
<script>
    const phone = document.getElementById('phone');
    phone.addEventListener('input', e => {
      let d = e.target.value.replace(/\D/g, '');
      if (d.startsWith('27')) d = '0' + d.slice(2);
      d = d.slice(0, 10);
      const parts = [d.slice(0, 3), d.slice(3, 6), d.slice(6, 10)].filter(Boolean);
      e.target.value = parts.join(' ');
    });

    // Registration modal script
    const registerButtons = document.querySelectorAll('.register-btn');
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');
    // Privacy modal script
    const privacyModal = document.getElementById('privacy-modal');
    const privacyModalCloseButtons = document.querySelectorAll('.privacy-modal-close, .privacy-modal-close-btn');
    const successModal = document.getElementById('success-modal');
    const successModalCloseButtons = document.querySelectorAll('.success-modal-close, .success-close-btn');
    const privacyLinks = document.querySelectorAll('.privacy-link-trigger');
    const registerForm = document.getElementById('register-form');
    const submitButton = registerForm.querySelector('button[type="submit"]');
    const emailInput = document.getElementById('reg-email');
    const firstConsent = document.querySelector('input[name="consent-main"]');
    const mediaConsent = document.querySelector('input[name="media-consent"]');
    const guardianConsent = document.querySelector('input[name="guardian-consent"]');

    function stripRichTextPaste(event) {
        const clipboard = event.clipboardData || window.clipboardData;
        if (!clipboard) return;

        const text = clipboard.getData('text/plain');
        if (!text) return;

        event.preventDefault();

        const input = event.target;
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        const nextValue = input.value.slice(0, start) + text + input.value.slice(end);

        input.value = nextValue.replace(/\s+/g, ' ').trim();
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="number"]').forEach((input) => {
        input.addEventListener('paste', stripRichTextPaste);
    });

    function updateSubmitState() {
        const hasEmail = emailInput.value.trim() !== '';
        const hasPhone = phone.value.trim() !== '';
        const readyToSubmit = hasEmail || hasPhone;

        submitButton.disabled = !readyToSubmit;
    }

    function validateRegistrationForm() {
        const requiredFields = [
            document.getElementById('register-fname'),
            document.getElementById('register-lname'),
            document.getElementById('register-age')
        ];

        for (const field of requiredFields) {
            if (!field.value.trim()) {
                field.focus();
                return false;
            }
        }

        if (!firstConsent.checked) {
            firstConsent.focus();
            return false;
        }

        if (Number(ageInput.value) < 18) {
            const guardianFieldsToCheck = [
                document.getElementById('guardian-fname'),
                document.getElementById('guardian-lname'),
                document.getElementById('register-guardRela'),
                document.getElementById('guardian-phone')
            ];

            for (const field of guardianFieldsToCheck) {
                if (!field.value.trim()) {
                    field.focus();
                    return false;
                }
            }

            if (!guardianConsent.checked) {
                guardianConsent.focus();
                return false;
            }
        }

        return true;
    }

    function openRegistrationModal(event) {
        event.preventDefault();
        modalOverlay.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function closeRegistrationModal() {
        modalOverlay.classList.remove('active');
        document.body.classList.remove('modal-open');
    }

    function openModal(modal) {
        modal.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        modal.classList.remove('active');
        const anyModalOpen = document.querySelector('.modal-overlay.active');
        if (!anyModalOpen) {
            document.body.classList.remove('modal-open');
        }
    }

    registerButtons.forEach((button) => {
        button.addEventListener('click', openRegistrationModal);
    });
    modalClose.addEventListener('click', closeRegistrationModal);
    modalOverlay.addEventListener('click', (event) => {
        if (event.target === modalOverlay) {
            closeRegistrationModal();
        }
    });

    privacyLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            openModal(privacyModal);
        });
    });

    privacyModalCloseButtons.forEach((button) => {
        button.addEventListener('click', () => closeModal(privacyModal));
    });

    privacyModal.addEventListener('click', (event) => {
        if (event.target === privacyModal) {
            closeModal(privacyModal);
        }
    });

    successModalCloseButtons.forEach((button) => {
        button.addEventListener('click', () => closeModal(successModal));
    });

    successModal.addEventListener('click', (event) => {
        if (event.target === successModal) {
            closeModal(successModal);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal(privacyModal);
            closeModal(successModal);
            closeRegistrationModal();
        }
    });

    // Success modal script
    registerForm.addEventListener('submit', (event) => {
        const hasEmail = emailInput.value.trim() !== '';
        const hasPhone = phone.value.trim() !== '';

        if (!hasEmail && !hasPhone) {
            event.preventDefault();
            submitButton.disabled = true;
            return;
        }

        if (!validateRegistrationForm()) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        closeRegistrationModal();
        openModal(successModal);
        registerForm.reset();
        updateGuardianFields();
        updateSubmitState();
    });

    emailInput.addEventListener('input', updateSubmitState);
    phone.addEventListener('input', updateSubmitState);
    updateSubmitState();

    const ageInput = document.getElementById('register-age');
    const guardianFields = document.getElementById('guardian-fields');
    const guardianInputs = guardianFields.querySelectorAll('input:not([type="checkbox"])');
    const guardianConsentInSection = guardianFields.querySelector('input[type="checkbox"]');

    function updateGuardianFields() {
        const rawAge = ageInput.value.trim();
        const parsedAge = Number(rawAge);
        const showGuardianFields = rawAge !== '' && !Number.isNaN(parsedAge) && parsedAge < 18;

        guardianFields.hidden = !showGuardianFields;

        guardianInputs.forEach((input) => {
            input.required = showGuardianFields;
        });
        guardianConsentInSection.required = showGuardianFields;

        if (!showGuardianFields) {
            guardianConsentInSection.checked = false;
            guardianConsent.checked = false;
        }
    }

    ageInput.addEventListener('input', updateGuardianFields);
    updateGuardianFields();
</script>
<?php include 'components/footer.php'; ?>
</body>

</html>