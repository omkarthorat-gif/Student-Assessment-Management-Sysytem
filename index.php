<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>T2_T3 Assessment Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0050e0;
            --primary-light: #3d7bff;
            --primary-dark: #003db1;
            --accent: #00d0ff;
            --text-dark: #222831;
            --text-light: #6c7983;
            --white: #ffffff;
            --card-shadow: 0 8px 20px rgba(0, 80, 224, 0.15);
            --card-hover-shadow: 0 15px 30px rgba(0, 80, 224, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f5ff, #ffffff);
            color: var(--text-dark);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .background-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(61, 123, 255, 0.1), rgba(0, 208, 255, 0.05));
            filter: blur(60px);
        }

        .shape-1 { width: 800px; height: 800px; top: -400px; left: -200px; }
        .shape-2 { width: 700px; height: 700px; bottom: -350px; right: -250px; }
        .shape-3 { width: 500px; height: 500px; top: 40%; left: 30%; background: linear-gradient(135deg, rgba(0, 208, 255, 0.1), rgba(61, 123, 255, 0.05)); }

        header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 2.5rem 0;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .vignan-logo {
    max-width: 250px; /* Increase this value as needed */
    height: auto;
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.3));
    transition: transform 0.4s ease;
}

.vignan-logo:hover { 
    transform: scale(1.06); 
}

.header-content {
    display: flex;
    align-items: center;
    gap: 15px; /* Adjust spacing between logo and heading */
    max-width: 100%; /* Ensures it doesn't grow beyond its parent */
    overflow: hidden; /* Prevents overflow issues */
}


        h1 {
            color: var(--white);
            font-size: 2.8rem;
            font-weight: 700;
            text-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
        }

        .header-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }

        .intro {
            text-align: center;
            margin-bottom: 3rem;
        }

        .intro h2 {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .intro h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 2px;
        }

        .intro p {
            font-size: 1rem;
            color: var(--text-light);
            max-width: 700px;
            margin: 1.5rem auto 0;
            line-height: 1.6;
        }

        .role-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .role-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 2.5rem 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.4s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }

        .role-card:hover {
            transform: translateY(-15px);
            box-shadow: var(--card-hover-shadow);
        }

        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .role-card:hover::before { opacity: 0.06; }

        .role-icon-wrapper {
            width: 140px;
            height: 140px;
            background: linear-gradient(135deg, rgba(61, 123, 255, 0.15), rgba(0, 208, 255, 0.15));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 80, 224, 0.12);
        }

        .role-card:hover .role-icon-wrapper {
            transform: scale(1.1);
            background: linear-gradient(135deg, rgba(61, 123, 255, 0.2), rgba(0, 208, 255, 0.2));
        }

        .role-icon {
            width: 90px;
            height: 90px;
            filter: drop-shadow(0 6px 12px rgba(0, 80, 224, 0.25));
            transition: transform 0.3s ease;
        }

        .role-card:hover .role-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 8px 15px rgba(0, 80, 224, 0.35));
        }

        .role-card h3 {
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.8rem;
        }

        .role-card p {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .role-btn {
            padding: 0.8rem 1.8rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(0, 80, 224, 0.2);
            position: relative;
            z-index: 1;
        }

        .role-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 80, 224, 0.35);
        }

        .role-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.4s ease;
            z-index: -1;
        }

        .role-btn:hover::before { left: 100%; }

        .footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
            background: linear-gradient(to top, rgba(240, 245, 255, 0.7), transparent);
        }

        @media (max-width: 1200px) {
            .role-container { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.8rem; }
            .role-icon-wrapper { width: 130px; height: 130px; }
            .role-icon { width: 80px; height: 80px; }
        }

        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 1rem; padding: 0 1rem; }
            h1 { font-size: 2rem; }
            .vignan-logo { width: 160px; }
            .role-container { grid-template-columns: 1fr; max-width: 400px; margin: 1.5rem auto 0; gap: 1.5rem; }
            .role-card { padding: 2rem 1.2rem; }
            .role-icon-wrapper { width: 120px; height: 120px; }
            .role-icon { width: 70px; height: 70px; }
            .intro h2 { font-size: 1.8rem; }
            .intro p { font-size: 0.9rem; padding: 0 0.8rem; }
            main { padding: 2rem 1rem; }
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        .floating { animation: floating 7s ease-in-out infinite; }
        .floating-delayed { animation: floating 7s ease-in-out 1.5s infinite; }
        .floating-slower { animation: floating 9s ease-in-out infinite; }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape shape-1 floating-slower"></div>
        <div class="shape shape-2 floating-delayed"></div>
        <div class="shape shape-3 floating"></div>
    </div>

    <header>
        <div class="header-content">
            <img src="assets/vignan.png" alt="Vignan University Logo" class="vignan-logo">
            <h1>T2 & T3 Assessment Portal</h1>
        </div>
        <div class="header-particles" id="headerParticles"></div>
    </header>

    <main>
        <div class="intro">
            <h2>Welcome to the Assessment Portal</h2>
            <p>Access and manage T2 and T3 assessments efficiently. Select your role below to proceed.</p>
        </div>

        <div class="role-container">
            <div class="role-card">
                <div class="role-icon-wrapper floating">
                    <img src="assets/admin.png" alt="Admin Icon" class="role-icon">
                </div>
                <h3>Administrator</h3>
                <p>Manage students, faculty, and assessments.</p>
                <button class="role-btn" data-href="admin/authentication/login.php">Login as Admin</button>
            </div>

            <div class="role-card">
                <div class="role-icon-wrapper floating-delayed">
                    <img src="assets/faculty.png" alt="Faculty Icon" class="role-icon">
                </div>
                <h3>Faculty</h3>
                <p>Review submissions and assign marks.</p>
                <button class="role-btn" data-href="faculty/authentication/login.php">Login as Faculty</button>
            </div>

            <div class="role-card">
                <div class="role-icon-wrapper floating-slower">
                    <img src="assets/student.png" alt="Student Icon" class="role-icon">
                </div>
                <h3>Student</h3>
                <p>Submit documents and view feedback.</p>
                <button class="role-btn" data-href="student/authentication/login.php">Login as Student</button>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>© 2025 Vignan University. All Rights Reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Particle animation for header
            const headerParticles = document.getElementById('headerParticles');
            const particleCount = 40;

            function createParticle(container) {
                const particle = document.createElement('div');
                particle.style.position = 'absolute';
                particle.style.borderRadius = '50%';
                const size = Math.random() * 8 + 3;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.background = 'rgba(255, 255, 255, 0.4)';
                particle.style.boxShadow = '0 0 12px rgba(255, 255, 255, 0.3)';
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                particle.style.opacity = '0';
                container.appendChild(particle);
                return particle;
            }

            function animateParticle(particle) {
                const speed = Math.random() * 20 + 15;
                const angle = Math.random() * Math.PI * 2;
                const distance = Math.random() * 100 + 50;
                const startX = parseFloat(particle.style.left);
                const startY = parseFloat(particle.style.top);
                let opacity = 0;
                let progress = 0;

                function step() {
                    progress += 0.005;
                    if (progress <= 0.2) {
                        opacity = progress / 0.2;
                    } else if (progress >= 0.8) {
                        opacity = (1 - progress) / 0.2;
                    } else {
                        opacity = 1;
                    }
                    particle.style.opacity = opacity.toString();
                    const x = startX + Math.cos(angle) * distance * progress;
                    const y = startY + Math.sin(angle) * distance * progress;
                    particle.style.transform = `translate(${x - startX}px, ${y - startY}px)`;
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    } else {
                        particle.style.opacity = '0';
                        particle.style.transform = 'translate(0, 0)';
                        particle.style.left = `${Math.random() * 100}%`;
                        particle.style.top = `${Math.random() * 100}%`;
                        setTimeout(() => animateParticle(particle), Math.random() * 1500);
                    }
                }
                setTimeout(() => requestAnimationFrame(step), Math.random() * 2000);
            }

            for (let i = 0; i < particleCount; i++) {
                const particle = createParticle(headerParticles);
                animateParticle(particle);
            }

            // Smooth button click handling
            const buttons = document.querySelectorAll('.role-btn');
            buttons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const href = button.getAttribute('data-href');
                    // Add a slight delay to allow button animation to complete
                    setTimeout(() => {
                        window.location.href = href;
                    }, 100);
                });
            });
        });
    </script>
</body>
</html>