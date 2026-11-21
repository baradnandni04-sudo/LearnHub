CREATE DATABASE IF NOT EXISTS learnhub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE learnhub;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    mobile VARCHAR(24) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL DEFAULT 'student',
    bio TEXT NULL,
    avatar_url VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    icon VARCHAR(80) NOT NULL DEFAULT 'bi-book',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instructor_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(210) NOT NULL UNIQUE,
    short_description VARCHAR(300) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(500) NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    level ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
    rating DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_courses_instructor FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_courses_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_courses_status_category (status, category_id),
    INDEX idx_courses_level (level),
    FULLTEXT INDEX ft_courses_search (title, short_description, description)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lessons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    video_url VARCHAR(1000) NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lessons_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_lessons_course_order (course_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment_student_course (student_id, course_id),
    CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_enrollments_course (course_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lesson_progress (
    student_id BIGINT UNSIGNED NOT NULL,
    lesson_id BIGINT UNSIGNED NOT NULL,
    completed_at TIMESTAMP NULL,
    PRIMARY KEY (student_id, lesson_id),
    CONSTRAINT fk_lesson_progress_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_progress_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quiz_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_option ENUM('a', 'b', 'c', 'd') NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_quiz_questions_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_quiz_questions_course_order (course_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quiz_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    score_percent TINYINT UNSIGNED NOT NULL,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_attempts_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempts_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_quiz_attempts_student_course (student_id, course_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS certificates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    certificate_code CHAR(12) NOT NULL UNIQUE,
    issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_certificate_student_course (student_id, course_id),
    CONSTRAINT fk_certificates_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_certificates_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO categories (name, icon) VALUES
    ('Design & Creativity', 'bi-palette'),
    ('Development', 'bi-code-slash'),
    ('Business', 'bi-briefcase'),
    ('Data & Analytics', 'bi-bar-chart-line'),
    ('Photography', 'bi-camera'),
    ('Personal Growth', 'bi-stars'),
    ('Marketing', 'bi-megaphone'),
    ('Lifestyle', 'bi-sun')
ON DUPLICATE KEY UPDATE icon = VALUES(icon);

-- Demo accounts use the password "password". Replace these credentials before deployment.
INSERT IGNORE INTO users (name, email, password_hash, role, bio)
VALUES
    ('LearnHub Admin', 'admin@learnhub.test', '$2y$10$NiwkAsnrGYGZMPjMp8ahBuEWhirZ1XND2q2ElMW5coaSflelnEXi6', 'admin', 'LearnHub platform administrator.'),
    ('Alex Morgan', 'instructor@learnhub.test', '$2y$10$NiwkAsnrGYGZMPjMp8ahBuEWhirZ1XND2q2ElMW5coaSflelnEXi6', 'instructor', 'Product designer and curious mind, sharing practical creative skills.');

INSERT IGNORE INTO courses
    (instructor_id, category_id, title, slug, short_description, description, image_url, price, level, rating, status)
SELECT u.id, c.id, seed.title, seed.slug, seed.short_description, seed.description, seed.image_url, 0, seed.level, seed.rating, 'published'
FROM users u
CROSS JOIN (
    SELECT 'Creative Coding for Curious Minds' AS title, 'creative-coding-curious-minds' AS slug,
           'Make your first interactive web project with friendly, hands-on lessons.' AS short_description,
           'Discover the joy of creative coding. Start with the building blocks of the web, experiment with color and motion, and finish by sharing a small interactive project of your own.' AS description,
           'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=85' AS image_url,
           'beginner' AS level, 4.9 AS rating, 'Development' AS category
    UNION ALL SELECT 'Design Thinking: From Idea to Impact', 'design-thinking-idea-to-impact',
           'A practical framework for turning thoughtful ideas into useful experiences.',
           'Learn to notice real-world needs, explore possibilities, and shape ideas into useful experiences. This approachable course gives you a repeatable design process and a small project to take away.',
           'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=900&q=85',
           'beginner', 4.8, 'Design & Creativity'
    UNION ALL SELECT 'Data Storytelling with Confidence', 'data-storytelling-confidence',
           'Turn numbers into clear, compelling stories people can act on.',
           'Build confidence with data by learning to ask better questions, choose clear visualizations, and explain what the numbers mean. Practice turning a simple dataset into a story for your audience.',
           'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=85',
           'intermediate', 4.9, 'Data & Analytics'
    UNION ALL SELECT 'A Beginner’s Guide to Better Photography', 'beginner-guide-better-photography',
           'See light differently and make thoughtful photos with the camera you have.',
           'Get to know light, framing, and visual storytelling. Simple exercises help you see familiar places in a new way and create stronger photos with a phone or camera.',
           'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?auto=format&fit=crop&w=900&q=85',
           'beginner', 4.7, 'Photography'
    UNION ALL SELECT 'Build a Brand People Remember', 'build-a-brand-people-remember',
           'Find your point of view and create a brand with a little more meaning.',
           'Explore the foundations of a memorable brand, from audience and purpose to voice and visual direction. Leave with a concise brand guide you can use in your next project.',
           'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=900&q=85',
           'intermediate', 4.8, 'Business'
) AS seed
INNER JOIN categories c ON c.name = seed.category
WHERE u.email = 'instructor@learnhub.test' AND u.role = 'instructor';

INSERT IGNORE INTO lessons (course_id, title, description, duration_minutes, sort_order)
SELECT co.id, seed.title, seed.description, seed.duration_minutes, seed.sort_order
FROM courses co
INNER JOIN (
    SELECT 'creative-coding-curious-minds' AS slug, 'A warm welcome to creative code' AS title,
           'Meet the course, set up your learning space, and start noticing the code behind the web.' AS description, 8 AS duration_minutes, 1 AS sort_order
    UNION ALL SELECT 'creative-coding-curious-minds', 'Your first tiny webpage',
           'Create a simple page and explore the building blocks that bring it to life.', 12, 2
    UNION ALL SELECT 'creative-coding-curious-minds', 'Color, motion, and playful ideas',
           'Add expressive details and make your small project your own.', 14, 3
    UNION ALL SELECT 'design-thinking-idea-to-impact', 'Start with a human question',
           'Learn how observation and good questions create a more useful starting point.', 10, 1
    UNION ALL SELECT 'design-thinking-idea-to-impact', 'Explore ideas without judgment',
           'Use simple prompts to broaden possibilities before choosing a direction.', 13, 2
    UNION ALL SELECT 'design-thinking-idea-to-impact', 'Make a first prototype',
           'Turn one idea into something you can share, test, and improve.', 16, 3
    UNION ALL SELECT 'data-storytelling-confidence', 'Ask a useful question',
           'Start with the decision your audience needs to make, then find the right evidence.', 11, 1
    UNION ALL SELECT 'data-storytelling-confidence', 'Choose a clear visual',
           'Match your message to a chart that makes the important pattern easy to see.', 15, 2
    UNION ALL SELECT 'data-storytelling-confidence', 'Share the story behind the numbers',
           'Create a focused narrative and make your next step clear.', 17, 3
    UNION ALL SELECT 'beginner-guide-better-photography', 'Notice the light around you',
           'Find soft light, observe its direction, and see how it changes a photograph.', 9, 1
    UNION ALL SELECT 'beginner-guide-better-photography', 'Frame a stronger image',
           'Use simple composition choices to make your subject stand out.', 12, 2
    UNION ALL SELECT 'beginner-guide-better-photography', 'Tell a small visual story',
           'Plan a few connected images that capture a moment with intention.', 14, 3
    UNION ALL SELECT 'build-a-brand-people-remember', 'Purpose before polish',
           'Define what your brand helps people do before reaching for visual details.', 12, 1
    UNION ALL SELECT 'build-a-brand-people-remember', 'Find your voice',
           'Use clear language and personality to help people recognize your brand.', 14, 2
    UNION ALL SELECT 'build-a-brand-people-remember', 'Bring the pieces together',
           'Create a compact guide you can use to keep your brand consistent.', 18, 3
) AS seed ON seed.slug = co.slug;

UPDATE lessons l
INNER JOIN courses co ON co.id = l.course_id
SET l.video_url = 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4'
WHERE co.slug = 'beginner-guide-better-photography' AND l.sort_order = 1 AND l.video_url IS NULL;

INSERT IGNORE INTO quiz_questions (course_id, question_text, option_a, option_b, option_c, option_d, correct_option, sort_order)
SELECT co.id, seed.question_text, seed.option_a, seed.option_b, seed.option_c, seed.option_d, seed.correct_option, seed.sort_order
FROM courses co
INNER JOIN (
    SELECT 'creative-coding-curious-minds' AS slug, 'What is a good first step when starting a creative coding project?' AS question_text,
           'Begin with one small idea' AS option_a, 'Build a full product at once' AS option_b,
           'Skip experimenting' AS option_c, 'Wait until every detail is perfect' AS option_d,
           'a' AS correct_option, 1 AS sort_order
    UNION ALL SELECT 'creative-coding-curious-minds', 'What is the goal of the course project?',
           'Create a small interactive webpage', 'Memorize every programming language', 'Buy a professional camera', 'Write a business plan', 'a', 2
    UNION ALL SELECT 'design-thinking-idea-to-impact', 'Design thinking is most useful when it starts with…',
           'A real human need', 'A finished visual style', 'A list of features', 'A guess about the solution', 'a', 1
    UNION ALL SELECT 'design-thinking-idea-to-impact', 'Why make a prototype?',
           'To make an idea tangible and learn from feedback', 'To avoid testing ideas', 'To finish every detail first', 'To replace conversations', 'a', 2
    UNION ALL SELECT 'data-storytelling-confidence', 'A clear data story should begin with…',
           'The audience and the question', 'The most complicated chart', 'Every available metric', 'A decorative color palette', 'a', 1
    UNION ALL SELECT 'data-storytelling-confidence', 'A visualization is most helpful when it…',
           'Makes a relevant pattern easier to understand', 'Contains as many labels as possible', 'Uses a different style for every value', 'Hides uncertainty', 'a', 2
    UNION ALL SELECT 'beginner-guide-better-photography', 'What is a useful first step in better photography?',
           'Pay attention to the available light', 'Use the most expensive camera', 'Avoid changing your framing', 'Add effects to every photo', 'a', 1
    UNION ALL SELECT 'beginner-guide-better-photography', 'Composition can help you…',
           'Guide attention to your subject', 'Change the weather', 'Guarantee every image is perfect', 'Replace a clear story', 'a', 2
    UNION ALL SELECT 'build-a-brand-people-remember', 'A useful place to begin building a brand is…',
           'Understanding its purpose and audience', 'Choosing a logo before anything else', 'Copying a competitor exactly', 'Adding more colors', 'a', 1
    UNION ALL SELECT 'build-a-brand-people-remember', 'A brand voice helps people recognize…',
           'How the brand communicates', 'How many products are in stock', 'The font file size', 'The project deadline', 'a', 2
) AS seed ON seed.slug = co.slug;
