-- Schema relationnel SQLite et donnees initiales de demonstration
-- Ce script configure les tables pour les promotions, les cours, les etudiants et leurs notes.

PRAGMA foreign_keys = OFF;

DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS promotions;

PRAGMA foreign_keys = ON;

-- Table des promotions (classes / cohortes)
CREATE TABLE promotions (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

-- Table des etudiants appartenant a une promotion
CREATE TABLE students (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    promotion_id INTEGER NOT NULL REFERENCES promotions(id),
    name         TEXT NOT NULL
);

-- Table des cours (matieres) avec leur coefficient respectif
CREATE TABLE courses (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    coefficient INTEGER NOT NULL DEFAULT 1 CHECK (coefficient > 0)
);

-- Table des notes obtenues par les etudiants dans les cours
CREATE TABLE grades (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL REFERENCES students(id),
    course_id  INTEGER NOT NULL REFERENCES courses(id),
    value      REAL NOT NULL CHECK (value >= 0 AND value <= 20),
    UNIQUE (student_id, course_id)
);

-- Insertion de la promotion de demonstration
INSERT INTO promotions (id, name) VALUES (1, 'Licence 3 Genie logiciel 2025-2026');

-- Insertion des 4 matieres de test avec coefficients varies
INSERT INTO courses (id, name, coefficient) VALUES
    (1, 'Maths', 2),
    (2, 'Programmation', 3),
    (3, 'Reseaux', 2),
    (4, 'Genie logiciel', 3);

-- Insertion des 4 etudiants de demonstration
INSERT INTO students (id, promotion_id, name) VALUES
    (1, 1, 'Amina B.'),
    (2, 1, 'Karim T.'),
    (3, 1, 'Salma D.'),
    (4, 1, 'Nadia R.');

-- Insertion des notes attribuees (permettant de verifier les moyennes et rangs)
INSERT INTO grades (student_id, course_id, value) VALUES
    (1, 1, 15), (1, 2, 17), (1, 3, 14), (1, 4, 16),
    (2, 1, 12), (2, 2, 11), (2, 3, 13), (2, 4, 10),
    (3, 1, 17), (3, 2, 16), (3, 3, 18), (3, 4, 15),
    (4, 1, 13), (4, 2, 14), (4, 3, 12), (4, 4, 15);
