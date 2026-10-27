<?php
require_once __DIR__ . '/includes/header.php';
?>

<main class="landing-page">
    <section class="hero" id="home">
        <div class="container hero-inner">
            <div class="hero-copy">
                <h1 class="reveal-title">
                    <span>Every</span>
                    <span>Child</span>
                    <span class="highlight">Protected</span>
                    <span>with</span>
                    <span class="highlight alt">Timely</span>
                    <span>Vaccines.</span>
                </h1>
                <p class="reveal-copy">
                    Stay on top of essential developmental immunizations with automated digital
                    schedules, verified clinical certificates, and seamless bookings at accredited pediatric hospitals.
                </p>
            </div>
        </div>

        <div class="container">
            <form class="search-panel" id="schedule" aria-label="Schedule search form" method="get" action="register_parent.php">
                <label class="field">
                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s6-5.4 6-11a6 6 0 1 0-12 0c0 5.6 6 11 6 11Zm0-8.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <select name="country" aria-label="Country">
                        <option value="">Select Country</option>
                        <option value="Afghanistan">Afghanistan</option>
                        <option value="Albania">Albania</option>
                        <option value="Algeria">Algeria</option>
                        <option value="Argentina">Argentina</option>
                        <option value="Australia">Australia</option>
                        <option value="Austria">Austria</option>
                        <option value="Bangladesh">Bangladesh</option>
                        <option value="Belgium">Belgium</option>
                        <option value="Brazil">Brazil</option>
                        <option value="Canada">Canada</option>
                        <option value="Chile">Chile</option>
                        <option value="China">China</option>
                        <option value="Colombia">Colombia</option>
                        <option value="Denmark">Denmark</option>
                        <option value="Egypt">Egypt</option>
                        <option value="France">France</option>
                        <option value="Germany">Germany</option>
                        <option value="Greece">Greece</option>
                        <option value="India">India</option>
                        <option value="Indonesia">Indonesia</option>
                        <option value="Iran">Iran</option>
                        <option value="Iraq">Iraq</option>
                        <option value="Ireland">Ireland</option>
                        <option value="Italy">Italy</option>
                        <option value="Japan">Japan</option>
                        <option value="Kenya">Kenya</option>
                        <option value="Malaysia">Malaysia</option>
                        <option value="Mexico">Mexico</option>
                        <option value="Morocco">Morocco</option>
                        <option value="Nepal">Nepal</option>
                        <option value="Netherlands">Netherlands</option>
                        <option value="Nigeria">Nigeria</option>
                        <option value="Norway">Norway</option>
                        <option value="Pakistan">Pakistan</option>
                        <option value="Philippines">Philippines</option>
                        <option value="Poland">Poland</option>
                        <option value="Portugal">Portugal</option>
                        <option value="Romania">Romania</option>
                        <option value="Saudi Arabia">Saudi Arabia</option>
                        <option value="Singapore">Singapore</option>
                        <option value="South Africa">South Africa</option>
                        <option value="South Korea">South Korea</option>
                        <option value="Spain">Spain</option>
                        <option value="Sri Lanka">Sri Lanka</option>
                        <option value="Sweden">Sweden</option>
                        <option value="Switzerland">Switzerland</option>
                        <option value="Thailand">Thailand</option>
                        <option value="Turkey">Turkey</option>
                        <option value="Ukraine">Ukraine</option>
                        <option value="United Arab Emirates">United Arab Emirates</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="United States">United States</option>
                        <option value="Vietnam">Vietnam</option>
                        <option value="Zimbabwe">Zimbabwe</option>
                    </select>
                    <span class="caret" aria-hidden="true">▾</span>
                </label>

                <label class="field">
                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M7 2v3M17 2v3M3 9h18M5 5h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input type="date" name="date" aria-label="Date" />
                </label>

                <label class="field select-field">
                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 3v18M3 12h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 7h10v10H7z" stroke="currentColor" stroke-width="1.7"/></svg>
                    </span>
                    <select name="vaccine" aria-label="Select vaccine">
                        <option value="">Select Target Vaccine</option>
                        <option value="polio">Polio</option>
                        <option value="measles">Measles</option>
                        <option value="mmr">MMR</option>
                        <option value="hepatitis">Hepatitis B</option>
                    </select>
                    <span class="caret" aria-hidden="true">▾</span>
                </label>

                <button type="submit" class="btn btn-primary register-btn">Register</button>
            </form>
        </div>
    </section>

    <section class="value-section" id="how-it-works">
        <div class="container value-inner">
            <div class="why-copy">
                <h2>Why Should We <span class="highlight">Get Vaccinated?</span></h2>
                <p>
                    Vaccines train the immune system safely before exposure. Safeguarding children from life-threatening illnesses prevents complications,
                    hospitalization, and irreversible damage.
                </p>
            </div>

            <div class="feature-grid" id="vaccines">
                <article class="feature-card">
                    <div class="feature-icon orange" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s-6-4.1-8.4-8.2C2.1 10.9 3.2 6 7.5 6c2 0 3.2 1 4.5 2.3C13.3 7 14.5 6 16.5 6 20.8 6 21.9 10.9 20.4 12.8 18 16.9 12 21 12 21Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h3>Prevent Severe Illness</h3>
                    <p>
                        Immunizations drastically reduce pediatric ICU admissions and eliminate complications from childhood diseases like polio, measles, and meningitis.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon green" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/></svg>
                    </div>
                    <h3>Stop Disease Spread</h3>
                    <p>
                        Timely shots break viral and bacterial transmission chains across daycare centers, primary schools, and neighborhood communities.
                    </p>
                </article>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
