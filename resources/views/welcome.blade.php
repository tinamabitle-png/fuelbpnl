<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetroFlow | Fuel BNPL Service</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0b0f1a;
            --midnight: #0f172a;
            --slate: #0b1220;
            --mist: #cbd5f5;
            --fog: #94a3b8;
            --glow: #f59e0b;
            --ember: #d97706;
            --spark: #fde68a;
            --card: rgba(17, 24, 39, 0.75);
            --glass: rgba(15, 23, 42, 0.55);
            --line: rgba(148, 163, 184, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Manrope", sans-serif;
            background: radial-gradient(1200px 800px at 10% 10%, #121b33 0%, #0b0f1a 60%),
                        radial-gradient(1000px 700px at 90% 0%, rgba(245, 158, 11, 0.15) 0%, transparent 50%),
                        #0b0f1a;
            color: #e2e8f0;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .noise {
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='160' height='160' filter='url(%23n)' opacity='0.08'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(9, 12, 20, 0.7);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: "Space Grotesk", sans-serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .logo .logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: radial-gradient(circle at 30% 30%, #fff1b2 0%, #f59e0b 40%, #b45309 100%);
            display: grid;
            place-items: center;
            color: #0b0f1a;
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.35);
        }

        .logo span {
            background: linear-gradient(90deg, #f59e0b, #fef3c7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 26px;
        }

        nav a {
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            position: relative;
            padding: 6px 0;
        }

        nav a::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            height: 2px;
            width: 0;
            background: linear-gradient(90deg, #f59e0b, #fde68a);
            transition: width 0.3s ease;
        }

        nav a:hover::after {
            width: 100%;
        }

        .header-cta {
            display: flex;
            gap: 12px;
        }

        .btn {
            border: none;
            font-family: "Space Grotesk", sans-serif;
            font-weight: 600;
            letter-spacing: 0.3px;
            padding: 12px 22px;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-ghost {
            background: transparent;
            color: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .btn-ghost:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.4);
        }

        .btn-primary {
            background: linear-gradient(120deg, #f59e0b, #f97316);
            color: #0b0f1a;
            box-shadow: 0 15px 30px rgba(245, 158, 11, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.01);
        }

        .hero {
            padding: 80px 0 120px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 50px;
            align-items: center;
        }

        .hero h1 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 56px;
            line-height: 1.05;
            margin-bottom: 20px;
        }

        .hero h1 span {
            background: linear-gradient(120deg, #f59e0b, #fef3c7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 18px;
            color: #a5b4fc;
            margin-bottom: 28px;
            max-width: 520px;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
        }

        .hero-meta {
            display: flex;
            gap: 28px;
        }

        .hero-meta .chip {
            background: var(--glass);
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 14px 18px;
            border-radius: 16px;
            min-width: 150px;
        }

        .chip h3 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 26px;
            color: #fef3c7;
        }

        .chip p {
            font-size: 13px;
            color: #94a3b8;
            margin: 0;
        }

        .hero-card {
            position: relative;
            background: linear-gradient(160deg, rgba(15, 23, 42, 0.9), rgba(2, 6, 23, 0.9));
            border-radius: 24px;
            padding: 30px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 20px 60px rgba(2, 6, 23, 0.7);
            overflow: hidden;
        }

        .hero-card::before,
        .hero-card::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.5), transparent 70%);
            filter: blur(20px);
            animation: float 6s ease-in-out infinite;
        }

        .hero-card::before {
            top: -120px;
            left: -80px;
        }

        .hero-card::after {
            bottom: -140px;
            right: -60px;
            animation-delay: 1.5s;
        }

        .hero-card .panel {
            position: relative;
            z-index: 2;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 18px;
            padding: 20px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            margin-bottom: 16px;
        }

        .panel h4 {
            font-family: "Space Grotesk", sans-serif;
            margin-bottom: 8px;
            font-size: 18px;
        }

        .panel p {
            color: #94a3b8;
            font-size: 14px;
        }

        .meter {
            height: 8px;
            background: rgba(148, 163, 184, 0.2);
            border-radius: 999px;
            overflow: hidden;
            margin-top: 12px;
        }

        .meter span {
            display: block;
            height: 100%;
            width: 68%;
            background: linear-gradient(90deg, #f59e0b, #fef3c7);
            animation: pulse 4s ease-in-out infinite;
        }

        .hero-card .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #fef3c7;
            border: 1px solid rgba(245, 158, 11, 0.4);
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.1);
        }

        .marquee {
            margin-top: 20px;
            display: flex;
            gap: 20px;
            overflow: hidden;
            padding: 12px 0;
            border-top: 1px solid rgba(148, 163, 184, 0.15);
        }

        .marquee-track {
            display: flex;
            gap: 20px;
            animation: marquee 18s linear infinite;
            white-space: nowrap;
        }

        .marquee span {
            font-size: 13px;
            color: #94a3b8;
            background: rgba(148, 163, 184, 0.1);
            padding: 6px 12px;
            border-radius: 999px;
        }

        .section {
            padding: 100px 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 38px;
        }

        .section-header p {
            max-width: 420px;
            color: #94a3b8;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .feature {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px;
            padding: 26px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 10%, rgba(245, 158, 11, 0.15), transparent 45%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.5);
        }

        .feature:hover::before {
            opacity: 1;
        }

        .feature i {
            font-size: 28px;
            color: #f59e0b;
            margin-bottom: 14px;
        }

        .feature h3 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .feature p {
            color: #94a3b8;
            font-size: 15px;
        }

        .insights {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .insight-card {
            background: linear-gradient(140deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.8));
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 22px;
            padding: 28px;
            display: grid;
            gap: 18px;
        }

        .insight-card h4 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 20px;
        }

        .timeline {
            display: grid;
            gap: 16px;
        }

        .timeline-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .timeline-item span {
            background: rgba(245, 158, 11, 0.15);
            color: #fef3c7;
            padding: 6px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .timeline-item p {
            color: #94a3b8;
            font-size: 14px;
        }

        .cta {
            margin: 120px auto 100px;
            border-radius: 28px;
            background: linear-gradient(120deg, rgba(245, 158, 11, 0.18), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(245, 158, 11, 0.35);
            padding: 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 36px;
            margin-bottom: 14px;
        }

        .cta p {
            color: #cbd5e1;
            margin-bottom: 28px;
        }

        footer {
            padding: 50px 0 30px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .footer-grid h4 {
            font-family: "Space Grotesk", sans-serif;
            margin-bottom: 16px;
        }

        .footer-grid a {
            text-decoration: none;
            color: #94a3b8;
            font-size: 14px;
        }

        .footer-grid a:hover {
            color: #fef3c7;
        }

        .footer-note {
            margin-top: 30px;
            font-size: 13px;
            color: #64748b;
            text-align: center;
        }

        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(18px); }
        }

        @keyframes pulse {
            0%, 100% { width: 68%; }
            50% { width: 80%; }
        }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        @media (max-width: 1024px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .hero-card {
                order: -1;
            }

            .grid-3 {
                grid-template-columns: 1fr 1fr;
            }

            .insights {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            nav {
                display: none;
            }

            .hero h1 {
                font-size: 42px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-meta {
                flex-direction: column;
            }

            .grid-3 {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .cta {
                padding: 36px 24px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="noise"></div>
    <header>
        <div class="container header-content">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-oil-can"></i></div>
                <span>PetroFlow</span>
            </div>
            <nav>
                <ul>
                    <li><a href="#overview">Overview</a></li>
                    <li><a href="#features">Benefits</a></li>
                    <li><a href="#insights">Insights</a></li>
                    <li><a href="#cta">Get Started</a></li>
                </ul>
            </nav>
            <div class="header-cta">
                <button class="btn btn-ghost">Sign In</button>
                <button class="btn btn-primary">Start Now</button>
            </div>
        </div>
    </header>

    <main>
        <section id="overview" class="container hero">
            <div>
                <h1>Fuel today.<br><span>Pay the smart way.</span></h1>
                <p>PetroFlow delivers premium fuel with frictionless BNPL scheduling. Track usage, automate payments, and keep your fleet moving without cash flow crunch.</p>
                <div class="hero-actions">
                    <button class="btn btn-primary">Apply in 2 Minutes</button>
                    <button class="btn btn-ghost">See Plans</button>
                </div>
                <div class="hero-meta">
                    <div class="chip">
                        <h3 data-count="25">0</h3>
                        <p>Thousand active users</p>
                    </div>
                    <div class="chip">
                        <h3 data-count="1.2">0</h3>
                        <p>Million gallons delivered</p>
                    </div>
                    <div class="chip">
                        <h3 data-count="98">0</h3>
                        <p>Percent satisfaction</p>
                    </div>
                </div>
            </div>
            <div class="hero-card reveal">
                <div class="badge"><i class="fas fa-bolt"></i> Approved in 90 seconds</div>
                <div class="panel">
                    <h4>Real-Time Fuel Pulse</h4>
                    <p>Live telemetry on your deliveries and usage.</p>
                    <div class="meter"><span></span></div>
                </div>
                <div class="panel">
                    <h4>Adaptive Payment Flow</h4>
                    <p>Split, pause, and align payments with revenue cycles.</p>
                    <div class="meter"><span></span></div>
                </div>
                <div class="panel">
                    <h4>Instant Route Match</h4>
                    <p>Nearest dispatch auto-selected for faster arrival.</p>
                    <div class="meter"><span></span></div>
                </div>
                <div class="marquee">
                    <div class="marquee-track">
                        <span>Fleet optimized</span>
                        <span>Verified quality</span>
                        <span>AI forecasting</span>
                        <span>0% hidden fees</span>
                        <span>Carbon smart</span>
                        <span>Instant approvals</span>
                        <span>Fleet optimized</span>
                        <span>Verified quality</span>
                        <span>AI forecasting</span>
                        <span>0% hidden fees</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="section">
            <div class="container">
                <div class="section-header">
                    <h2>Modern fuel operations, reimagined</h2>
                    <p>Built for logistics teams, independent operators, and high-velocity fleets that can’t afford downtime.</p>
                </div>
                <div class="grid-3">
                    <div class="feature reveal">
                        <i class="fas fa-calendar-check"></i>
                        <h3>Smart Scheduling</h3>
                        <p>Predictive delivery windows tuned to your routes and storage levels.</p>
                    </div>
                    <div class="feature reveal">
                        <i class="fas fa-chart-line"></i>
                        <h3>Instant Insights</h3>
                        <p>Dashboards that surface anomalies, spend trends, and optimization wins.</p>
                    </div>
                    <div class="feature reveal">
                        <i class="fas fa-location-dot"></i>
                        <h3>Live Dispatch</h3>
                        <p>GPS-tracked delivery progress with dynamic ETA updates.</p>
                    </div>
                    <div class="feature reveal">
                        <i class="fas fa-shield"></i>
                        <h3>Verified Quality</h3>
                        <p>Certified supply chain checks with batch-level reporting.</p>
                    </div>
                    <div class="feature reveal">
                        <i class="fas fa-wallet"></i>
                        <h3>Flexible BNPL</h3>
                        <p>Split payments across cycles with zero surprise fees.</p>
                    </div>
                    <div class="feature reveal">
                        <i class="fas fa-leaf"></i>
                        <h3>Lower Emissions</h3>
                        <p>Route pooling and smart batching reduce carbon footprint.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="insights" class="section">
            <div class="container insights">
                <div class="insight-card reveal">
                    <h4>Fuel Control Center</h4>
                    <p>Live risk scoring, payment health, and supply readiness in one cinematic view.</p>
                    <div class="timeline">
                        <div class="timeline-item">
                            <span>08:30</span>
                            <p>Predictive restock flagged 12 hours ahead.</p>
                        </div>
                        <div class="timeline-item">
                            <span>10:10</span>
                            <p>Auto-route to nearest verified depot activated.</p>
                        </div>
                        <div class="timeline-item">
                            <span>12:05</span>
                            <p>BNPL schedule synced to weekly revenue cycle.</p>
                        </div>
                    </div>
                </div>
                <div class="insight-card reveal">
                    <h4>Partners who move fast</h4>
                    <p>Trusted by fleet operators, construction teams, and on-demand services across 38 states.</p>
                    <div class="panel" style="margin: 0;">
                        <h4>Average Savings</h4>
                        <p>17% reduction in idle downtime within 30 days.</p>
                        <div class="meter"><span></span></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="cta" class="container cta reveal">
            <h2>Unlock instant fuel credit</h2>
            <p>Apply in minutes, get verified in seconds, and keep vehicles moving with confidence.</p>
            <button class="btn btn-primary">Get My Fuel Line</button>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>PetroFlow</h4>
                    <p style="color: #94a3b8; font-size: 14px;">Fuel BNPL made for modern fleets and on-demand operations.</p>
                    <div style="display: flex; gap: 12px; margin-top: 12px;">
                        <i class="fab fa-twitter"></i>
                        <i class="fab fa-linkedin-in"></i>
                        <i class="fab fa-instagram"></i>
                    </div>
                </div>
                <div>
                    <h4>Solutions</h4>
                    <a href="#">Fleet Plans</a><br>
                    <a href="#">Independent Operators</a><br>
                    <a href="#">Emergency Delivery</a>
                </div>
                <div>
                    <h4>Company</h4>
                    <a href="#">About</a><br>
                    <a href="#">Careers</a><br>
                    <a href="#">Press</a>
                </div>
                <div>
                    <h4>Support</h4>
                    <a href="#">Help Center</a><br>
                    <a href="#">Security</a><br>
                    <a href="#">Contact</a>
                </div>
            </div>
            <div class="footer-note">&copy; 2026 PetroFlow. All rights reserved.</div>
        </div>
    </footer>

    <script>
        const revealItems = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.15 });

        revealItems.forEach(item => observer.observe(item));

        const counters = document.querySelectorAll('[data-count]');
        const animateCounter = (el) => {
            const target = parseFloat(el.dataset.count);
            const isFloat = String(target).includes('.');
            let current = 0;
            const step = target / 60;
            const tick = () => {
                current += step;
                if (current >= target) {
                    current = target;
                }
                el.textContent = isFloat ? current.toFixed(1) : Math.floor(current);
                if (current < target) {
                    requestAnimationFrame(tick);
                }
            };
            requestAnimationFrame(tick);
        };

        counters.forEach(counter => animateCounter(counter));
    </script>
</body>
</html>
