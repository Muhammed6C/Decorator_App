PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS promotions (
    id INTEGER PRIMARY KEY,
    nom TEXT NOT NULL,
    annee INTEGER NOT NULL,
    UNIQUE (nom, annee)
);

CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY,
    nom TEXT NOT NULL,
    prenom TEXT NOT NULL,
    promotion_id INTEGER NOT NULL,
    FOREIGN KEY (promotion_id) REFERENCES promotions (id)
);

CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY,
    intitule TEXT NOT NULL UNIQUE,
    coefficient REAL NOT NULL CHECK (coefficient > 0)
);

CREATE TABLE IF NOT EXISTS grades (
    id INTEGER PRIMARY KEY,
    student_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    note REAL NOT NULL CHECK (note >= 0 AND note <= 20),
    UNIQUE (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_students_promotion ON students (promotion_id);
CREATE INDEX IF NOT EXISTS idx_grades_student ON grades (student_id);
CREATE INDEX IF NOT EXISTS idx_grades_course ON grades (course_id);

INSERT OR IGNORE INTO promotions (id, nom, annee) VALUES
    (1, 'L3 Informatique', 2024);

INSERT OR IGNORE INTO students (id, nom, prenom, promotion_id) VALUES
    (1, 'Ndiaye', 'Fatou', 1),
    (2, 'Diop', 'Amadou', 1),
    (3, 'Fall', 'Awa', 1),
    (4, 'Sarr', 'Moussa', 1);

INSERT OR IGNORE INTO courses (id, intitule, coefficient) VALUES
    (1, 'Algorithmique avancée', 4),
    (2, 'Bases de données', 3),
    (3, 'Réseaux & Protocoles', 2),
    (4, 'Mathématiques discrètes', 3),
    (5, 'Développement Web', 3),
    (6, 'Systèmes d''exploitation', 2);

INSERT OR IGNORE INTO grades (student_id, course_id, note) VALUES
    (1, 1, 15.5), (1, 2, 13.0), (1, 3, 11.5),
    (1, 4, 14.0), (1, 5, 16.0), (1, 6, 12.5),
    (2, 1, 11.0), (2, 2, 14.5), (2, 3, 13.0),
    (2, 4, 10.5), (2, 5, 12.0), (2, 6, 15.0),
    (3, 1, 17.5), (3, 2, 16.0), (3, 3, 15.0),
    (3, 4, 18.0), (3, 5, 14.5), (3, 6, 16.5),
    (4, 1, 8.5), (4, 2, 10.0), (4, 3, 9.0),
    (4, 4, 11.5), (4, 5, 10.5), (4, 6, 9.5);
