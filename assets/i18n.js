/* ============================================================
   OAK International School — Traduction FR / EN (client-side)
   Dictionnaire statique, sans dépendance externe (fiable, hors-ligne).
   ============================================================ */
(function () {
  "use strict";

  function norm(s) {
    return s.replace(/\s+/g, " ").trim();
  }

  // Dictionnaire français (clé normalisée) -> anglais
  var DICT = {
    "Accueil": "Home",
    "Collège (6e–Tle)": "Middle & High School (6th–12th)",
    "Confidentialité": "Privacy",
    "Connexion": "Login",
    "Contact": "Contact",
    "Cycles": "Cycles",
    "Demander une pré-inscription": "Request pre-registration",
    "Discipline · Travail · Succès": "Discipline · Work · Success",
    "Galerie": "Gallery",
    "Internat": "Boarding School",
    "Lun – Ven : 7h30 – 17h30": "Mon – Fri: 7:30am – 5:30pm",
    "Mentions légales": "Legal Notice",
    "Navigation": "Navigation",
    "Nos Cycles": "Our Cycles",
    "Nous contacter": "Contact us",
    "Pré-inscription": "Pre-registration",
    "Résultats": "Results",
    "Samedi : 8h00 – 13h00": "Saturday: 8:00am – 1:00pm",
    "Série A (Littéraire)": "Series A (Arts & Literature)",
    "Série B (Économique)": "Series B (Economics)",
    "Série C (Scientifique)": "Series C (Science)",
    "Série D (Sciences-Nature)": "Series D (Life Sciences)",
    "Tableau d'honneur": "Honor Roll",
    "« Former l'excellence d'aujourd'hui pour demain »": "“Shaping today's excellence for tomorrow”",
    "À Propos": "About",
    "École Primaire (Maternelle–CM2)": "Primary School (Kindergarten–5th grade)",
    "© 2026 OAK International School — Cotonou, Bénin. Tous droits réservés.": "© 2026 OAK International School — Cotonou, Benin. All rights reserved.",
    "Rang national": "National rank",
    "Réussite BAC 2025": "BAC Success 2025",
    "Réussite BEPC 2025": "BEPC Success 2025",
    "En images": "In pictures",
    ", de la maternelle au secondaire, notre établissement conjugue excellence académique, innovation pédagogique et valeurs humaines dans un cadre d'apprentissage moderne, sécurisé et inspirant.":
      ", from kindergarten to secondary school, our institution combines academic excellence, educational innovation and human values in a modern, safe and inspiring learning environment.",
    ", professeur de mathématiques, le": ", a mathematics teacher, the",
    "-- Choisir --": "-- Select --",
    "-- Choisir un sujet --": "-- Select a subject --",
    "8 niveaux": "8 grade levels",
    "800 apprenants": "800 students",
    "Accueillant plus de": "Welcoming more than",
    "Activités sportives": "Sports activities",
    "Adresse e-mail": "Email address",
    "Années": "Years",
    "Au quotidien": "Day to day",
    "Bibliothèque numérique": "Digital library",
    "Collège": "Middle School",
    "Depuis sa création en 2001 par": "Since its founding in 2001 by",
    "Départements : Maternelle, Primaire, Collège, Internat": "Departments: Kindergarten, Primary, Middle School, Boarding School",
    "Déposer une demande": "Submit a request",
    "En savoir plus": "Learn more",
    "Enseignants qualifiés": "Qualified teachers",
    "Envoyer ma demande": "Send my request",
    "Excellence académique": "Academic excellence",
    "Grâce à un corps enseignant hautement qualifié, à un suivi individualisé et à une pédagogie exigeante, nos élèves obtiennent année après année d'excellents résultats aux examens officiels, confirmant la réputation d'excellence de notre institution.":
      "Thanks to a highly qualified teaching staff, personalized support and a demanding pedagogy, our students achieve excellent results year after year in official examinations, confirming our institution's reputation for excellence.",
    "Hébergement": "Accommodation",
    "Infirmerie": "Infirmary",
    "Informatique": "Computer Science",
    "Juillet – Août 2026": "July – August 2026",
    "Laboratoire de sciences": "Science laboratory",
    "Le OAK International School dispose également d'un": "The school also has a",
    "Maternelle – CM2": "Kindergarten – 5th grade",
    "Moyenne": "Average",
    "Nous appeler": "Call us",
    "Qui sommes-nous ?": "Who are we?",
    "Le mot de la direction": "A word from the school leadership",
    "Bienvenue à Oak International School": "Welcome to Oak International School",
    "Au nom de l'ensemble du personnel, je vous souhaite la plus chaleureuse bienvenue dans notre école,":
      "On behalf of the entire staff, I warmly welcome you to our school,",
    ". Mon objectif en dirigeant l'école est de continuer à offrir et à développer un enseignement de qualité exceptionnelle, et je m'engage à fournir une éducation du plus haut niveau pour votre/vos enfant(s). L'enseignement est totalement inclusif, et les cours sont stimulants et exigeants, afin de répondre aux intérêts, au plaisir et à la motivation intrinsèque de chaque élève.":
      ". My goal in leading the school is to continue offering and developing exceptional quality teaching, and I am committed to providing the highest level of education for your child(ren). Teaching is fully inclusive, and lessons are stimulating and demanding, to meet the interests, enjoyment and intrinsic motivation of every student.",
    ". Mon objectif en dirigeant l'école est de continuer à offrir et à développer un enseignement de qualité exceptionnelle, et je m'engage à fournir une éducation du plus haut niveau pour votre/vos enfant(s).":
      ". My goal in leading the school is to continue offering and developing exceptional quality teaching, and I am committed to providing the highest level of education for your child(ren).",
    "Oak International School est un lieu particulier, où le personnel est dédié à offrir un environnement stimulant et enthousiasmant, où chacun se sent valorisé et en sécurité, où les idées peuvent s'épanouir et où les élèves peuvent réaliser pleinement leur potentiel. Grâce à un programme structuré et engageant et à la promotion d'un bon comportement, nous visons à éveiller chez tous les enfants l'amour de l'apprentissage, ainsi que le respect et la bienveillance envers les autres.":
      "Oak International School is a special place, where staff are dedicated to providing a stimulating and exciting environment, where everyone feels valued and safe, where ideas can flourish and where students can fully realize their potential. Through a structured and engaging curriculum and the promotion of good behavior, we aim to instill in every child a love of learning, as well as respect and kindness toward others.",
    "Oak International School est un lieu particulier, où le personnel est dédié à offrir un environnement stimulant et enthousiasmant, où chacun se sent valorisé et en sécurité, où les idées peuvent s'épanouir et où les élèves peuvent réaliser pleinement leur potentiel.":
      "Oak International School is a special place, where staff are dedicated to providing a stimulating and exciting environment, where everyone feels valued and safe, where ideas can flourish and where students can fully realize their potential.",
    "Quel que soit le besoin ou l'intérêt particulier de votre enfant, nous nous efforçons de garantir qu'il/elle s'épanouisse ici, qu'il/elle se sente en sécurité, qu'il/elle prenne plaisir à apprendre et qu'il/elle réussisse avec succès.":
      "Whatever your child's particular need or interest, we strive to ensure that they thrive here, feel safe, enjoy learning and succeed.",
    "Nous souhaitons que le temps passé par nos élèves à l'école soit inoubliable, qu'il s'appuie sur leurs connaissances et compétences actuelles, et qu'il les mène à devenir des apprenants tout au long de la vie. Nous attachons une grande importance au partenariat entre la maison et l'école, et nous encourageons tous les parents à prendre une part active dans l'éducation de leurs enfants. L'école ne peut pas fonctionner en isolement.":
      "We want the time our students spend at school to be unforgettable, to build on their current knowledge and skills, and to lead them to become lifelong learners. We place great importance on the partnership between home and school, and we encourage all parents to take an active part in their children's education. A school cannot operate in isolation.",
    "Le partenariat entre le personnel scolaire, les parents, le conseil d'administration et les autres parties prenantes est essentiel. Ensemble, nous pouvons fixer des défis, et nos partenaires peuvent nous aider à nous demander comment faire encore mieux. J'espère que vous trouverez toutes les informations nécessaires sur notre école. Si vous avez besoin de renseignements supplémentaires, n'hésitez pas à nous contacter.":
      "The partnership between school staff, parents, the board and other stakeholders is essential. Together, we can set challenges, and our partners can help us ask how we can do even better. I hope you will find all the information you need about our school. If you need any further information, please do not hesitate to contact us.",
    "Je vous souhaite une agréable visite sur notre site web. Je serai ravie de vous rencontrer si vous souhaitez visiter notre école. Nous sommes ouverts et heureux de répondre à toutes les questions des parents, alors n'hésitez pas à appeler notre bureau si vous souhaitez en savoir plus. Merci de votre visite sur notre site !":
      "I wish you a pleasant visit to our website. I would be delighted to meet you should you wish to visit our school. We are open and happy to answer any questions from parents, so please do not hesitate to call our office if you would like to know more. Thank you for visiting our site!",
    "Rejoindre OAK International School": "Join OAK International School",
    "Salle informatique": "Computer room",
    "Septembre 2026": "September 2026",
    "Téléphone": "Phone",
    "Voir tous les lauréats": "See all award winners",
    "cultive une ambition forte : former des élèves compétents, responsables et préparés aux défis de demain.":
      "cultivates a strong ambition: to shape competent, responsible students prepared for the challenges of tomorrow.",
    "d'Excellence": "of Excellence",
    "internat": "boarding school",
    "offrant un environnement serein, un encadrement permanent et des conditions optimales pour favoriser la réussite scolaire et l'épanouissement personnel de chaque élève.":
      "offering a peaceful environment, constant supervision and optimal conditions to promote each student's academic success and personal development.",
    "page de contact": "contact page",
    "politique de confidentialité": "privacy policy",
    "sont obligatoires.": "are required.",
    "École Primaire": "Primary School",
    "Élèves inscrits": "Enrolled students",
    "Épanouissement personnel": "Personal development",
    ", établissement privé laïc d'enseignement secondaire, situé Rue Pharmaquick, Akpakpa, Cotonou, République du Bénin.":
      ", a private secular secondary school located on Rue Pharmaquick, Akpakpa, Cotonou, Republic of Benin.",
    ", établissement privé laïc d'enseignement secondaire basé à Akpakpa, Cotonou, engagé depuis 2001 dans la réussite scolaire, l'épanouissement personnel et la formation citoyenne des jeunes béninois.":
      ", a private secular secondary school based in Akpakpa, Cotonou, committed since 2001 to academic success, personal development and civic education of young Beninese people.",
    "0 / 20 caractères minimum": "0 / 20 characters minimum",
    "12h00 – 14h00": "12:00pm – 2:00pm",
    "12h30 – 15h30": "12:30pm – 3:30pm",
    "13 niveaux": "13 grade levels",
    "15h30 – 17h30": "3:30pm – 5:30pm",
    "16h30 – 18h30": "4:30pm – 6:30pm",
    "19h00 – 19h45": "7:00pm – 7:45pm",
    "1er Trimestre": "1st Term",
    "1ère semaine Oct. 2026": "1st week of Oct. 2026",
    "20h00 – 21h30": "8:00pm – 9:30pm",
    "21 Déc – 5 Jan 2027": "Dec 21 – Jan 5, 2027",
    "22h00": "10:00pm",
    "28 Oct – 3 Nov 2026": "Oct 28 – Nov 3, 2026",
    "2e Trimestre": "2nd Term",
    "3 repas par jour, 7 jours sur 7": "3 meals a day, 7 days a week",
    "35 élèves max": "35 students max",
    "35 élèves max / classe": "35 students max / class",
    "3e Trimestre": "3rd Term",
    "4 niveaux": "4 grade levels",
    "40 postes connectés, cours de programmation": "40 connected computers, coding classes",
    "40 postes connectés, initiation au code.": "40 connected computers, introduction to coding.",
    "40 postes, cours de programmation": "40 computers, coding classes",
    "5h30 – 7h00": "5:30am – 7:00am",
    "7h30 – 12h30": "7:30am – 12:30pm",
    "7h30 – 17h30": "7:30am – 5:30pm",
    "8h00 – 13h00": "8:00am – 1:00pm",
    "9h45 – 10h00": "9:45am – 10:00am",
    "Accompagnement personnalisé pour les élèves en difficulté.": "Personalized support for students facing difficulties.",
    "Accueil | OAK International School": "Home | OAK International School",
    "Activités parascolaires variées": "Various extracurricular activities",
    "Adaptation au cycle secondaire, méthode de travail et organisation.": "Adjustment to secondary school, study methods and organization.",
    "Adresse": "Address",
    "E-mail": "Email",
    "Rue Pharmaquick, en face Société LABOREX": "Rue Pharmaquick, opposite Société LABOREX",
    "Cité Vie Nouvelle, Akpakpa, Cotonou, Bénin": "Cité Vie Nouvelle, Akpakpa, Cotonou, Benin",
    "Agenda": "Schedule",
    "Aide aux devoirs et révisions chaque soir en semaine.": "Homework help and review every weekday evening.",
    "Aire de jeux": "Playground",
    "Allemand": "German",
    "Ancien élève — Promotion 2020": "Former student — Class of 2020",
    "Anglais": "English",
    "Annuler": "Cancel",
    "Année": "Year",
    "Année :": "Year:",
    "Année scolaire 2026/2027 — Tous les champs marqués": "2026/2027 school year — All fields marked",
    "Appels autorisés le soir et sorties encadrées un week-end sur deux.": "Phone calls allowed in the evening and supervised outings every other weekend.",
    "Apprendre en français, maîtriser l'anglais, s'ouvrir au monde": "Learn in French, master English, open up to the world",
    "Apprentissage de la lecture, de l'écriture cursive et des opérations de base.": "Learning to read, cursive writing and basic arithmetic.",
    "Approfondissement des matières fondamentales, langues vivantes, sciences, séries A, B, C & D et préparation au BEPC comme au BAC.":
      "Deepening core subjects, foreign languages, sciences, Series A, B, C & D, and preparation for both the BEPC and the BAC.",
    "Approfondissement, autonomie et préparation au BEPC et au BAC.": "Deeper learning, independence and preparation for the BEPC and BAC.",
    "Appréciation": "Assessment",
    "Arts & Culture": "Arts & Culture",
    "Autre": "Other",
    "Avril – Juin 2027": "April – June 2027",
    "Besoin d'une réponse immédiate ?": "Need an immediate answer?",
    "Bibliothèque et documentation pour approfondir les apprentissages.": "Library and resources to deepen learning.",
    "Blanchisserie": "Laundry",
    "Bureau des besoins éducatifs spéciaux": "Special Educational Needs Office",
    "Bâtiment principal": "Main building",
    "Cafétéria": "Cafeteria",
    "Cafétéria sur place": "On-site cafeteria",
    "Calendrier 2026/2027": "2026/2027 Calendar",
    "Calendrier des admissions": "Admissions calendar",
    "Calendrier des inscriptions": "Enrollment calendar",
    "Cantine & cuisine": "Canteen & kitchen",
    "Ce qu'on enseigne, niveau par niveau": "What we teach, level by level",
    "Ce que disent nos familles": "What our families say",
    "Ce qui est inclus dans les frais d'internat": "What's included in boarding fees",
    "Ce site est hébergé par un prestataire d'hébergement web tiers. Les coordonnées complètes de l'hébergeur sont disponibles sur simple demande auprès de l'établissement.":
      "This site is hosted by a third-party web hosting provider. Full hosting provider details are available on request from the school.",
    "Ce site peut utiliser des cookies techniques nécessaires à son bon fonctionnement. Aucun cookie de suivi publicitaire tiers n'est utilisé sans votre consentement.":
      "This site may use technical cookies necessary for its proper functioning. No third-party advertising tracking cookies are used without your consent.",
    "Censeur / Directrice adjointe": "Vice Principal / Deputy Head",
    "Centre de ressources": "Resource Center",
    "Centre de soutien scolaire": "Academic Support Center",
    "Ces données sont utilisées exclusivement pour répondre à vos demandes, assurer le suivi des dossiers de pré-inscription et vous recontacter si nécessaire. Elles ne sont ni vendues, ni cédées à des tiers à des fins commerciales.":
      "This data is used exclusively to respond to your requests, follow up on pre-registration files and contact you again if necessary. It is never sold or transferred to third parties for commercial purposes.",
    "Ces élèves incarnent l'ambition et le travail qui font la fierté de toute la communauté des Élites. Félicitations à chacun d'eux !":
      "These students embody the ambition and hard work that make the whole Élites community proud. Congratulations to each of them!",
    "Ces élèves ont brillé par leurs résultats et représentent la fierté de toute la communauté des Élites.":
      "These students shone through their results and represent the pride of the whole Élites community.",
    "Chambres filles et garçons distinctes, literie moderne.": "Separate rooms for girls and boys, modern bedding.",
    "Chant": "Singing",
    "Chant & Comptines": "Singing & Nursery Rhymes",
    "Chaque élève est particulièrement suivi par le corps enseignant et les membres de l'administration.": "Each student is closely monitored by the teaching staff and school administration.",
    "Choisissez le bon cycle pour votre enfant": "Choose the right cycle for your child",
    "Classe souhaitée": "Desired class",
    "Classe souhaitée *": "Desired class *",
    "Club de lecture": "Reading club",
    "Coloriage": "Coloring",
    "Communication régulière, bulletins numériques.": "Regular communication, digital report cards.",
    "Communication régulière, conseils de classe ouverts et bulletins numériques accessibles à tout moment.": "Regular communication, open parent-teacher meetings and digital report cards available anytime.",
    "Concours d’éloquence": "Public speaking contest",
    "Confidentialité | OAK International School": "Privacy | OAK International School",
    "Confirmation & dossier d'inscription": "Confirmation & enrollment file",
    "Conformément à la réglementation en vigueur, vous disposez d'un droit d'accès, de rectification et de suppression des données vous concernant. Pour exercer ces droits, contactez-nous via notre":
      "In accordance with applicable regulations, you have the right to access, rectify and delete your personal data. To exercise these rights, contact us via our",
    "Congés scolaires": "School holidays",
    "Conservation": "Data retention",
    "Contact | OAK International School": "Contact | OAK International School",
    "Contactez-nous": "Contact us",
    "Cookies": "Cookies",
    "Cour de récréation": "Playground",
    "Cours du matin": "Morning classes",
    "Curiosité intellectuelle et ouverture sur le monde": "Intellectual curiosity and openness to the world",
    "Cérémonie d’ouverture": "Opening ceremony",
    "Date de naissance": "Date of birth",
    "De la maternelle au Baccalauréat, chaque cycle est pensé pour préparer l'élève à l'étape suivante avec rigueur et bienveillance.":
      "From kindergarten to the Baccalaureate, every cycle is designed to prepare students for the next step with rigor and care.",
    "De la maternelle au Terminale, chaque cycle aux Élites est conçu pour bâtir sur le précédent — avec les mêmes exigences, les mêmes valeurs et un suivi personnalisé à chaque étape.":
      "From kindergarten to the final year, each cycle at Élites is designed to build on the previous one — with the same standards, the same values, and personalized support at every stage.",
    "Demande de Pré-inscription": "Pre-registration Request",
    "Demande de pré-inscription": "Pre-registration request",
    "Demande rapide — nous vous rappelons": "Quick request — we'll call you back",
    "Demander une place à l'internat": "Request a boarding place",
    "Demi-journée": "Half-day",
    "Des enseignants qualifiés, des programmes adaptés et un suivi individualisé pour chaque élève.": "Qualified teachers, tailored programs and personalized support for every student.",
    "Des élèves qui illustrent chaque année la réputation d'excellence de l'établissement.": "Students who embody the school's reputation for excellence every year.",
    "Dessin": "Drawing",
    "Deux cycles pour un parcours complet": "Two cycles for a complete education",
    "Dictée": "Dictation",
    "Directeur de publication": "Publication director",
    "Directeur du Primaire": "Primary School Director",
    "Directeur fondateur": "Founding Director",
    "Disponibles": "Available",
    "Document scolaire": "School document",
    "Donner à chaque enfant les bases intellectuelles, morales et physiques nécessaires pour développer sa créativité, renforcer sa confiance en lui et devenir progressivement autonome et responsable.":
      "Give every child the intellectual, moral and physical foundations needed to develop their creativity, build self-confidence and gradually become independent and responsible.",
    "Données collectées": "Data collected",
    "Données sécurisées — jamais revendues": "Secure data — never resold",
    "Dortoir, literie, entretien des locaux": "Dormitory, bedding, facility upkeep",
    "Dortoirs": "Dormitories",
    "Dortoirs séparés": "Separate dormitories",
    "Du primaire au baccalauréat": "From primary school to the Baccalaureate",
    "Découverte des sciences, premières rédactions et introduction à l'anglais.": "Introduction to science, first essays and introduction to English.",
    "Déjeuner, puis cours de l'après-midi": "Lunch, then afternoon classes",
    "Dépôt des dossiers de pré-inscription": "Submission of pre-registration files",
    "Dépôt du dossier": "File submission",
    "Détail des programmes": "Program details",
    "Développement de l'esprit critique": "Development of critical thinking",
    "Dîner": "Dinner",
    "EPS": "P.E.",
    "Encadrement": "Supervision",
    "Enceinte clôturée, accès contrôlé et gardiennage permanent pour la tranquillité des familles.": "Enclosed grounds, controlled access and round-the-clock security for families' peace of mind.",
    "Enseignants qualifiés et expérimentés": "Qualified and experienced teachers",
    "Entretien / Test niveau": "Interview / Placement test",
    "Environnement sécurisé et bienveillant": "Safe and caring environment",
    "Envoyer le message": "Send message",
    "Envoyez-nous un message": "Send us a message",
    "Espace extérieur dédié aux activités sportives et tournois.": "Outdoor space dedicated to sports activities and tournaments.",
    "Espace sécurisé de détente et de jeux pour les plus jeunes.": "Safe play and relaxation area for younger children.",
    "Espagnol": "Spanish",
    "Examen": "Exam",
    "Examen : BEPC": "Exam: BEPC",
    "Excellence récompensée": "Excellence rewarded",
    "Excellent": "Excellent",
    "Excursion pédagogique": "Educational field trip",
    "Expression créative": "Creative expression",
    "Expression écrite": "Written expression",
    "Expériences pratiques en physique, chimie et biologie.": "Hands-on experiments in physics, chemistry and biology.",
    "Extinction des feux, coucher": "Lights out, bedtime",
    "Faire une demande": "Submit a request",
    "Football, basketball, handball, athlétisme": "Football, basketball, handball, athletics",
    "Format PDF, JPEG ou PNG — 8 Mo maximum.": "PDF, JPEG or PNG format — 8 MB maximum.",
    "Former l'excellence d'aujourd'hui pour le Bénin de demain.": "Shaping today's excellence for tomorrow's Benin.",
    "Formes & Couleurs": "Shapes & Colors",
    "Formulaire de pré-inscription": "Pre-registration form",
    "Français": "French",
    "Fraternité": "Brotherhood",
    "Féminin": "Female",
    "Fête culturelle": "Cultural festival",
    "Galerie photo & vidéo": "Photo & video gallery",
    "Galerie | OAK International School": "Gallery | OAK International School",
    "Grammaire avancée, problèmes complexes, premières leçons de méthode.": "Advanced grammar, complex problems, first lessons in study methods.",
    "Grandes vacances": "Summer holidays",
    "Graphisme": "Pattern drawing",
    "Graphisme, langage oral, formes et couleurs, autonomie.": "Pattern drawing, oral language, shapes and colors, independence.",
    "Heures d'accueil": "Opening hours",
    "Heures d'ouverture": "Opening hours",
    "Histoire-Géo": "History-Geography",
    "Honnêteté et transparence dans chaque acte": "Honesty and transparency in every action",
    "Horaires & Rythme scolaire": "Schedule & School Routine",
    "Ils sont recrutés sur la base des tests en vigueur en République du Bénin, de leur moralité et de leur expérience professionnelle.":
      "They are recruited based on the tests required in the Republic of Benin, their moral character and their professional experience.",
    "Ils témoignent": "Testimonials",
    "Inauguration labo": "Lab inauguration",
    "Informations du parent / tuteur": "Parent / guardian information",
    "Informations légales": "Legal information",
    "Informations pratiques": "Practical information",
    "Informations sur l'élève": "Student information",
    "Infrastructures modernes": "Modern facilities",
    "Initiation au calcul": "Introduction to arithmetic",
    "Inscription 2026/2027": "Enrollment 2026/2027",
    "Inscrivez votre enfant": "Enroll your child",
    "Instant de partage": "A moment of togetherness",
    "Intensification, examens blancs BEPC/BAC": "Intensive review, mock BEPC/BAC exams",
    "Internat | OAK International School": "Boarding School | OAK International School",
    "Introduction aux notions avancées et orientation progressive.": "Introduction to advanced concepts and gradual guidance.",
    "Intégrer OAK International School": "Join OAK International School",
    "Intégrez une école où chaque moment compte et chaque réussite est célébrée.": "Join a school where every moment matters and every success is celebrated.",
    "Intégrité": "Integrity",
    "Itinéraire sur Google Maps": "Directions on Google Maps",
    "J'accepte que les informations saisies soient utilisées dans le cadre exclusif du traitement de cette demande de pré-inscription, conformément à la":
      "I agree that the information entered will be used exclusively to process this pre-registration request, in accordance with the",
    "J'accepte que mes données soient utilisées pour traiter ma demande, conformément à notre":
      "I agree that my data will be used to process my request, in accordance with our",
    "J'ai eu mon BAC série C avec mention Très Bien grâce à la préparation intensive des profs d'OAK International School. Aujourd'hui je suis en 2e année de médecine. Merci à toute l'équipe !":
      "I got my BAC in Series C with high honors thanks to the intensive preparation from OAK International School's teachers. Today I'm in my second year of medical school. Thanks to the whole team!",
    "Janvier – Mars 2027": "January – March 2027",
    "Jeux éducatifs": "Educational games",
    "Journée complète": "Full day",
    "Journée portes ouvertes": "Open house day",
    "Juillet – Septembre 2027": "July – September 2027",
    "L'effort régulier comme clé de la réussite": "Consistent effort as the key to success",
    "L'ensemble des contenus présents sur ce site (textes, images, logo, mise en page) est la propriété du OAK International School, sauf mention contraire, et ne peut être reproduit, distribué ou exploité sans autorisation préalable.":
      "All content on this site (text, images, logo, layout) is the property of OAK International School, unless otherwise stated, and may not be reproduced, distributed or used without prior authorization.",
    "L'excellence académique ne suffit pas. Chez OAK International School, nous cultivons aussi la créativité, le sport, la citoyenneté et la vie en collectivité.":
      "Academic excellence is not enough. At OAK International School, we also nurture creativity, sport, citizenship and community life.",
    "L'internat accueille les élèves du CM1 à la Terminale, dans la limite des places disponibles chaque année.":
      "The boarding school welcomes students from CM1 (5th grade) to the final year, subject to available places each year.",
    "L'équipe administrative": "The administrative team",
    "La direction du OAK International School est responsable de la publication du présent site.": "The management of OAK International School is responsible for publishing this site.",
    "La musique": "Music",
    "La vie aux Élites": "Life at Élites",
    "La vie à OAK International School": "Life at OAK International School",
    "La vie à l'internat": "Boarding school life",
    "Laboratoires de sciences": "Science laboratories",
    "Langage oral": "Oral language",
    "Langue : Français + Anglais dès CE1": "Language: French + English from CE1 (2nd grade)",
    "Lauréats du BEPC": "BEPC award winners",
    "Le futur de vos enfants": "The future of your children",
    "Le présent site est édité par le": "This site is published by",
    "Le rythme de vie à l'internat": "Daily life at the boarding school",
    "Lecture & Écriture": "Reading & Writing",
    "Les bases solides : lire, écrire, compter et comprendre le monde.": "Solid foundations: reading, writing, counting and understanding the world.",
    "Les données sont conservées pendant la durée nécessaire au traitement de votre demande, puis archivées ou supprimées conformément aux obligations légales applicables aux établissements scolaires.":
      "Data is kept for as long as necessary to process your request, then archived or deleted in accordance with the legal obligations applicable to schools.",
    "Les places à l'internat sont": "Boarding places are",
    "Les pré-inscriptions ouvrent généralement en juillet pour l'année suivante. Consultez la page Pré-inscription pour les dates exactes.":
      "Pre-registration generally opens in July for the following year. Check the Pre-registration page for exact dates.",
    "Les pré-inscriptions pour l'année scolaire": "Pre-registration for the school year",
    "Les pré-inscriptions pour l'année scolaire 2026/2027 sont ouvertes. Places limitées — rejoignez une communauté d'excellence.":
      "Pre-registration for the 2026/2027 school year is open. Limited places — join a community of excellence.",
    "Les visages qui accompagnent chaque élève et chaque famille au quotidien.": "The faces who support every student and every family every day.",
    "Les études du soir sont encadrées par des enseignants qualifiés. Inscription disponible dès la rentrée.":
      "Evening study sessions are supervised by qualified teachers. Registration available from the start of the school year.",
    "Lessive hebdomadaire du linge personnel": "Weekly laundry of personal clothing",
    "Lettres, philosophie, langues et sciences humaines.": "Literature, philosophy, languages and humanities.",
    "Lien avec les familles": "Connection with families",
    "Loisirs encadrés": "Supervised leisure activities",
    "Lorsque vous utilisez nos formulaires (pré-inscription, contact), nous collectons uniquement les informations nécessaires au traitement de votre demande : nom, prénom, coordonnées de contact, et informations relatives à la scolarité de l'élève concerné.":
      "When you use our forms (pre-registration, contact), we only collect the information necessary to process your request: first and last name, contact details, and information related to the student's schooling.",
    "Lundi – Vendredi": "Monday – Friday",
    "Maternelle 1 – CM2": "Kindergarten 1 – 5th grade",
    "Mathématiques": "Mathematics",
    "Mathématiques avancées, physique-chimie et sciences de la vie.": "Advanced mathematics, physics-chemistry and life sciences.",
    "Matières enseignées": "Subjects taught",
    "Meilleur résultat · BEPC 2024": "Best result · BEPC 2024",
    "Mentions TB": "High Honors",
    "Mentions TB BAC": "High Honors – BAC",
    "Mentions TB BEPC": "High Honors – BEPC",
    "Mentions légales | OAK International School": "Legal Notice | OAK International School",
    "Mes deux enfants sont scolarisés ici depuis le primaire. L'ambiance est excellente, la discipline bienveillante et les résultats sont au rendez-vous. Je recommande sans hésiter.":
      "Both of my children have been enrolled here since primary school. The atmosphere is excellent, the discipline is caring and the results are there. I recommend it without hesitation.",
    "Message": "Message",
    "Mi-Octobre 2026": "Mid-October 2026",
    "Mission · Vision · Valeurs": "Mission · Vision · Values",
    "Modalités & tarifs": "Terms & fees",
    "Moment de convivialité": "A friendly moment",
    "Mon fils a intégré OAK International School en 6e. Aujourd'hui en Terminale, il a le niveau, la méthode et la confiance pour réussir son BAC. Les enseignants sont vraiment présents et à l'écoute.":
      "My son joined OAK International School in 6th grade. Now in his final year, he has the level, the method and the confidence to pass his BAC. The teachers are truly present and attentive.",
    "Motricité": "Motor skills",
    "Moy.": "Avg.",
    "Moyenne générale": "Overall average",
    "Ne jamais abandonner face aux défis": "Never give up in the face of challenges",
    "Nom complet": "Full name",
    "Nom complet du parent / tuteur": "Full name of parent / guardian",
    "Nom de l'élève": "Student's name",
    "Nom de l'élève *": "Student's name *",
    "Nom": "Last name",
    "Prénom": "First name",
    "Nos Cycles d'Enseignement": "Our Education Cycles",
    "Nos Cycles | OAK International School": "Our Cycles | OAK International School",
    "Nos Valeurs": "Our Values",
    "Nos formations": "Our programs",
    "Nos infrastructures ont été conçues pour offrir les meilleures conditions d'apprentissage : salles climatisées, laboratoires équipés, bibliothèque numérique et espaces sportifs.":
      "Our facilities have been designed to offer the best learning conditions: air-conditioned classrooms, equipped laboratories, digital library and sports areas.",
    "Nos installations": "Our facilities",
    "Nos lauréats": "Our award winners",
    "Nos meilleurs lauréats": "Our top award winners",
    "Nos moments forts": "Our highlights",
    "Nos performances": "Our performance",
    "Notre Internat": "Our Boarding School",
    "Notre Mission": "Our Mission",
    "Notre Vision": "Our Vision",
    "Notre boussole": "Our compass",
    "Notre histoire": "Our history",
    "Notre localisation": "Our location",
    "Notre offre pédagogique": "Our educational offering",
    "Notre personnel": "Our staff",
    "Notre secrétariat est disponible du lundi au samedi.": "Our office is available Monday through Saturday.",
    "Notre tableau d'honneur": "Our honor roll",
    "Nous sommes à votre écoute": "We're here to listen",
    "Nous vous contactons sous 48h": "We'll contact you within 48 hours",
    "Noël": "Christmas",
    "Octobre (sem. 1) 2026": "October (week 1) 2026",
    "Octobre 2026": "October 2026",
    "Octobre – Décembre 2026": "October – December 2026",
    "Optionnel — sur inscription": "Optional — by registration",
    "Organisation": "Organization",
    "Ou remplissez le": "Or fill out the",
    "Oui, notre cafétéria propose des repas équilibrés préparés sur place, du lundi au vendredi.": "Yes, our cafeteria offers balanced meals prepared on-site, Monday through Friday.",
    "Oui, un programme de bourses au mérite est disponible pour les élèves ayant obtenu d'excellents résultats au BEPC ou en classe.":
      "Yes, a merit-based scholarship program is available for students who have achieved excellent results in the BEPC or in class.",
    "Ouverture": "Opening",
    "Où nous trouver": "Where to find us",
    "Parent d'élève — Classe de 3e": "Parent — 3ème (9th grade)",
    "Parent d'élève — Double inscription": "Parent — Two children enrolled",
    "Depuis que ma fille est à OAK International School, elle a gagné en confiance et en autonomie. Le suivi personnalisé des enseignants fait toute la différence.":
      "Since my daughter joined OAK International School, she has gained confidence and independence. The teachers' personalized support makes all the difference.",
    "Parent d'élève — Classe de CM2": "Parent — 5th grade class",
    "Le sérieux de l'encadrement et la qualité des enseignants ont permis à mon fils de décrocher une mention au BAC. Une école qui tient ses promesses.":
      "The seriousness of the supervision and the quality of the teachers helped my son earn honors on his BAC. A school that keeps its promises.",
    "Parent d'élève — Classe de Terminale": "Parent — Final year class",
    "OAK International School m'a donné les bases solides pour réussir mes études supérieures. Je garde un excellent souvenir de mes années ici.":
      "OAK International School gave me the solid foundations to succeed in higher education. I have excellent memories of my years here.",
    "Ancienne élève — Promotion 2018": "Former student — Class of 2018",
    "L'internat a rassuré toute la famille. Mon fils est bien encadré, bien nourri et suit sérieusement ses cours du soir.":
      "The boarding school reassured the whole family. My son is well supervised, well fed and takes his evening study seriously.",
    "Parent d'élève — Classe de 6e": "Parent — 6th grade class",
    "Mes trois enfants sont scolarisés à OAK International School. La communication avec les enseignants est excellente et les résultats parlent d'eux-mêmes.":
      "All three of my children are enrolled at OAK International School. Communication with the teachers is excellent and the results speak for themselves.",
    "Parent d'élève — Triple inscription": "Parent — Three children enrolled",
    "Partenariat / Presse": "Partnership / Press",
    "Partenariat avec les parents": "Partnership with parents",
    "Partenariat parents": "Parent partnership",
    "Pause déjeuner": "Lunch break",
    "Performances dans le temps": "Performance over time",
    "Personnel de santé disponible pour les premiers soins.": "Health staff available for first aid.",
    "Persévérance": "Perseverance",
    "Philosophie": "Philosophy",
    "Physique, Chimie, SVT — équipements modernes": "Physics, Chemistry, Biology — modern equipment",
    "Physique-Chimie": "Physics-Chemistry",
    "Places": "Places",
    "Planifiez": "Plan",
    "Plus de 25 ans d'excellence": "Over 25 years of excellence",
    "Plus de 5 000 ouvrages physiques et digitaux": "Over 5,000 physical and digital books",
    "Plus de 800 élèves formés chaque année, du cycle primaire au collège, dans un cadre moderne et bienveillant au cœur de Cotonou.":
      "More than 800 students trained every year, from primary school to middle school, in a modern and caring environment in the heart of Cotonou.",
    "Politique de confidentialité": "Privacy Policy",
    "Poser une question": "Ask a question",
    "Pour les élèves venant de loin ou dont les parents recherchent un cadre structurant, le OAK International School propose un internat mixte à taille humaine, au sein même de l'établissement. Dortoirs séparés filles et garçons, surveillance permanente et rythme de vie régulier : tout est pensé pour que chaque interne se sente en sécurité et puisse se consacrer pleinement à ses études.":
      "For students coming from far away or whose parents are looking for a structured environment, OAK International School offers a small-scale co-ed boarding facility within the school itself. Separate dormitories for girls and boys, round-the-clock supervision and a steady daily routine: everything is designed so that every boarder feels safe and can fully focus on their studies.",
    "Pour toute question relative aux présentes mentions légales, vous pouvez nous contacter via notre":
      "For any question regarding this legal notice, you can contact us via our",
    "Pourquoi OAK International School ?": "Why OAK International School?",
    "Pourquoi un internat ?": "Why a boarding school?",
    "Processus d'admission": "Admission process",
    "Programmes ambitieux, enseignants qualifiés.": "Ambitious programs, qualified teachers.",
    "Proposez-vous des bourses ?": "Do you offer scholarships?",
    "Propriété intellectuelle": "Intellectual property",
    "Pré-inscription | OAK International School": "Pre-registration | OAK International School",
    "Prélecture": "Pre-reading",
    "Prélecture, préécriture et préparation à l'entrée au CP.": "Pre-reading, pre-writing and preparation for first grade.",
    "Prénom *": "First name *",
    "Prénom de l'élève": "Student's first name",
    "Préparation intensive au BAC série A, dissertations et oral.": "Intensive preparation for the BAC Series A, essays and oral exams.",
    "Préparation intensive au BAC série B, études de cas et concours.": "Intensive preparation for the BAC Series B, case studies and competitive exams.",
    "Préparation intensive au BAC série C, TP et concours grandes écoles.": "Intensive preparation for the BAC Series C, lab work and top-school entrance exams.",
    "Préparation intensive au BAC série D, TP et concours grandes écoles.": "Intensive preparation for the BAC Series D, lab work and top-school entrance exams.",
    "Préparation à l'enseignement supérieur": "Preparation for higher education",
    "Préécriture": "Pre-writing",
    "Publication des résultats d'admission": "Publication of admission results",
    "Pâques": "Easter",
    "Quand ouvrent les inscriptions ?": "When does enrollment open?",
    "Questions fréquentes": "Frequently asked questions",
    "Rang Dép.": "Dept. rank",
    "Rang Nat.": "Nat. rank",
    "Rang départemental": "Departmental rank",
    "Recrutement des professeurs": "Teacher recruitment",
    "Rejoignez l'aventure": "Join the adventure",
    "Rejoignez un établissement où l'excellence est une tradition. Les pré-inscriptions pour 2026/2027 sont ouvertes.":
      "Join a school where excellence is a tradition. Pre-registration for 2026/2027 is open.",
    "Rejoignez-nous": "Join us",
    "Remise de diplômes": "Graduation ceremony",
    "Remise de prix annuelle": "Annual awards ceremony",
    "Remplissez le formulaire ci-contre": "Fill out the form opposite",
    "Renforcement disciplinaire, développement de l'esprit d'analyse.": "Stronger discipline, development of analytical thinking.",
    "Renseignements généraux": "General information",
    "Rentrée scolaire": "Back to school",
    "Rentrée scolaire officielle": "Official start of the school year",
    "Rentrée, évaluation diagnostique, conseil de classe": "Back to school, diagnostic assessment, class council",
    "Repas équilibrés préparés sur place": "Balanced meals prepared on-site",
    "Repas équilibrés préparés sur place chaque jour.": "Balanced meals prepared on-site every day.",
    "Restauration": "Catering",
    "Retours sur nos événements, cérémonies, activités sportives et moments de réussite qui font la richesse de notre communauté scolaire.":
      "A look back at our events, ceremonies, sports activities and moments of success that make up the richness of our school community.",
    "Récompenses académiques": "Academic awards",
    "Récréation matin": "Morning break",
    "Rédaction structurée, fractions, géométrie et anglais parlé.": "Structured writing, fractions, geometry and spoken English.",
    "Réponse garantie sous 48h ouvrables. Les champs marqués": "Response guaranteed within 48 business hours. Fields marked",
    "Réseaux sociaux": "Social media",
    "Résultats & Tableau d'Honneur": "Results & Honor Roll",
    "Résultats & Tableau d'Honneur | OAK International School": "Results & Honor Roll | OAK International School",
    "Résultats d'admission": "Admission results",
    "Réveil, toilette, petit-déjeuner": "Wake up, hygiene, breakfast",
    "Révisions approfondies et entraînement intensif au Certificat d'Études Primaires.": "In-depth review and intensive training for the Primary School Certificate.",
    "Révisions finales, examens officiels, remise de prix": "Final review, official exams, awards ceremony",
    "Révisions intensives, examens blancs et préparation au BEPC officiel.": "Intensive review, mock exams and preparation for the official BEPC.",
    "SVT": "Biology",
    "Salle de classe": "Classroom",
    "Salle de musique": "Music room",
    "Samedi": "Saturday",
    "Sciences": "Science",
    "Sciences de la Vie": "Life Sciences",
    "Sciences de la vie et de la terre, avec mathématiques et physique-chimie.": "Life and earth sciences, with mathematics and physics-chemistry.",
    "Sciences, Langues, Maths, Lettres": "Science, Languages, Math, Literature",
    "Scolarité / Bulletins": "Tuition / Report cards",
    "Secrétaire administrative": "Administrative secretary",
    "Selon calendrier 2027": "According to the 2027 calendar",
    "Service de lessive hebdomadaire inclus pour le linge personnel des internes.": "Weekly laundry service included for boarders' personal clothing.",
    "Sexe": "Gender",
    "Sorties encadrées un week-end sur deux": "Supervised outings every other weekend",
    "Sport, arts et activités parascolaires.": "Sports, arts and extracurricular activities.",
    "Sport, culture, arts : nous cultivons toutes les dimensions de la personnalité de l'élève.": "Sports, culture, arts: we nurture every dimension of each student's personality.",
    "Sport, douche, temps libre": "Sports, shower, free time",
    "Sport, jeux collectifs et temps libre organisés les soirs et le week-end.": "Sports, group games and organized free time in the evenings and on weekends.",
    "Suivi adapté pour les élèves à besoins éducatifs particuliers.": "Tailored support for students with special educational needs.",
    "Suivi des élèves": "Student monitoring",
    "Suivi individualisé de chaque élève": "Personalized support for every student",
    "Sujet": "Subject",
    "Surveillance 24h/24": "24/7 supervision",
    "Surveillant général": "Head of Discipline",
    "Surveillants et éducateurs présents jour et nuit.": "Supervisors and educators present day and night.",
    "Surveillants et étude du soir": "Supervisors and evening study",
    "Séance d’arts plastiques": "Art class",
    "Séance d’informatique": "Computer class",
    "Sécurité du site": "Site security",
    "Série A": "Series A",
    "Série B": "Series B",
    "Série C": "Series C",
    "Série D": "Series D",
    "Tableau d'honneur 2024": "2024 Honor Roll",
    "Taux de réussite": "Success rate",
    "Taux de réussite supérieur à 95%": "Success rate above 95%",
    "Terrain de sport": "Sports field",
    "Terrains multisports": "Multi-sport fields",
    "Test de niveau (si nécessaire)": "Placement test (if necessary)",
    "Tests de niveau et entretiens": "Placement tests and interviews",
    "Théâtre, musique, dessin et ateliers créatifs.": "Theater, music, drawing and creative workshops.",
    "Tournoi inter-classes": "Inter-class tournament",
    "Tournois inter-classes chaque trimestre": "Inter-class tournaments every term",
    "Tous examens": "All exams",
    "Toussaint": "All Saints' Day",
    "Tout est prévu, jusqu'au moindre détail": "Everything is planned, down to the smallest detail",
    "Tout voir": "View all",
    "Toutes": "All",
    "Transmission des savoirs fondamentaux": "Passing on fundamental knowledge",
    "Travail": "Work",
    "Trois repas équilibrés par jour, préparés sur place par notre cafétéria, avec un menu adapté aux besoins des adolescents.":
      "Three balanced meals a day, prepared on-site by our cafeteria, with a menu suited to teenagers' needs.",
    "Tronc commun avec accent sur les lettres et sciences humaines.": "Core curriculum with a focus on literature and humanities.",
    "Tronc commun avec accent sur les mathématiques et la physique-chimie.": "Core curriculum with a focus on mathematics and physics-chemistry.",
    "Tronc commun avec accent sur les sciences de la vie et de la terre.": "Core curriculum with a focus on life and earth sciences.",
    "Tronc commun avec initiation à l'économie et à la gestion.": "Core curriculum with an introduction to economics and management.",
    "Téléphone :": "Phone:",
    "Téléphone parent *": "Parent's phone *",
    "Un cadre confortable, sécurisé et propice à la concentration, du réveil au coucher.": "A comfortable, safe setting conducive to concentration, from wake-up to bedtime.",
    "Un cadre de vie chaleureux": "A warm living environment",
    "Un cadre moderne au service de l'élève": "A modern setting for students",
    "Un climat scolaire bienveillant et inclusif": "A caring and inclusive school climate",
    "Un parcours complet, cohérent et ambitieux": "A complete, coherent and ambitious educational path",
    "Un second foyer,": "A second home,",
    "Une infirmière est disponible sur le campus ; les parents sont informés en cas de besoin médical.": "A nurse is available on campus; parents are informed in case of medical need.",
    "Une institution bâtie sur": "An institution built on",
    "Une journée type": "A typical day",
    "Une place à l'internat": "A boarding place",
    "Une question ?": "A question?",
    "Une vie scolaire riche et épanouissante": "A rich and fulfilling school life",
    "Utilisation des données": "Use of data",
    "Vie en communauté": "Community life",
    "Vie scolaire": "School life",
    "Visite scientifique": "Science field trip",
    "Voir les infrastructures": "See our facilities",
    "Voir tous les programmes": "See all programs",
    "Voir toute la galerie": "See the full gallery",
    "Vos données, protégées": "Your data, protected",
    "Vos droits": "Your rights",
    "Votre enfant mérite le meilleur": "Your child deserves the best",
    "Votre enfant pourrait être dans ces photos": "Your child could be in these photos",
    "Votre enfant sera le prochain lauréat": "Your child could be the next award winner",
    "Votre tour": "Your turn",
    "Vous hésitez sur le niveau d'entrée ? Notre équipe pédagogique est disponible pour vous guider et organiser un test de positionnement.":
      "Not sure which entry level is right? Our teaching team is available to guide you and arrange a placement test.",
    "Week-ends": "Weekends",
    "Y a-t-il une cantine sur place ?": "Is there an on-site canteen?",
    "et attribuées par ordre d'inscription, en priorité aux élèves résidant hors de Cotonou. Les frais d'internat (hébergement, restauration, blanchisserie) sont facturés en plus des frais de scolarité et communiqués sur simple demande au secrétariat.":
      "and allocated in order of registration, with priority given to students living outside Cotonou. Boarding fees (accommodation, meals, laundry) are billed in addition to tuition fees and provided on request from the office.",
    "formulaire complet": "full form",
    "la rigueur et la passion": "rigor and passion",
    "limitées à 80": "limited to 80",
    "maître": "teacher",
    "pensé pour la réussite": "designed for success",
    "pour la rentrée 2026/2027": "for the 2026/2027 school year",
    "pour plus de détails.": "for more details.",
    "se prépare ici": "is prepared here",
    "sont ouvertes. Ne tardez pas, les places sont limitées.": "is open. Don't wait, places are limited.",
    "À Propos du OAK International School": "About OAK International School",
    "À Propos | OAK International School": "About | OAK International School",
    "Économie": "Economics",
    "Économie, gestion, comptabilité et mathématiques appliquées.": "Economics, management, accounting and applied mathematics.",
    "Éditeur du site": "Site publisher",
    "Étape 1": "Step 1",
    "Étape 2": "Step 2",
    "Étape 3": "Step 3",
    "Étape 4": "Step 4",
    "Étude du soir": "Evening study",
    "Étude du soir encadrée": "Supervised evening study",
    "Études surveillées": "Supervised study",
    "Étudier, s'épanouir et grandir dans un environnement d'excellence, avec un internat offrant confort, sécurité et accompagnement au quotidien.":
      "Studying, thriving and growing in an environment of excellence, with a boarding school offering comfort, safety and daily support.",
    "Éveil": "Early learning",
    "Éveil musical et pratique instrumentale": "Musical awakening and instrumental practice",
    "Éveil musical et pratique instrumentale encadrée.": "Supervised musical awakening and instrumental practice.",
    "Éveil sensoriel": "Sensory awakening",
    "Éveil sensoriel, motricité, premiers repères et vie en collectivité.": "Sensory awakening, motor skills, first bearings and community life.",
    "Éveil, lecture, écriture, mathématiques et découverte du monde. Un socle solide pour la suite du parcours.":
      "Early learning, reading, writing, mathematics and discovering the world. A solid foundation for the rest of the journey.",
    "Évolution des taux de réussite": "Success rate trends",
    "Être l'établissement de référence au Bénin, reconnu pour la qualité de son corps enseignant, l'excellence de ses résultats et l'impact positif de ses anciens élèves sur la société béninoise et africaine.":
      "To be the leading school in Benin, recognized for the quality of its teaching staff, the excellence of its results and the positive impact of its alumni on Beninese and African society.",
    "à OAK International School": "at OAK International School",
    "— 10 ans": "— 10 years old",
    "— 11 ans": "— 11 years old",
    "— 12 ans": "— 12 years old",
    "— 13 ans": "— 13 years old",
    "— 14 ans": "— 14 years old",
    "— 15 ans": "— 15 years old",
    "— 16 ans": "— 16 years old",
    "— 17 ans": "— 17 years old",
    "— 18 ans": "— 18 years old",
    "— 3 ans": "— 3 years old",
    "— 4 ans": "— 4 years old",
    "— 5 ans": "— 5 years old",
    "— 6 ans": "— 6 years old",
    "— 8 ans": "— 8 years old",
    "— 9 ans": "— 9 years old",
    // Placeholders / accessibilité
    "Décrivez votre demande en détail…": "Describe your request in detail…",
    "Ex : M. KOFFI Jean": "E.g.: Mr. KOFFI Jean",
    "Ex : Amandine": "E.g.: Amandine",
    "Ex : KOFFI": "E.g.: KOFFI",
    "Ex : M. KOFFI Jean-Pierre": "E.g.: Mr. KOFFI Jean-Pierre",
    "Ex : parent@email.com": "E.g.: parent@email.com",
    "Ex : 97 00 00 00": "E.g.: 97 00 00 00",
    "Espace administration": "Admin area",
    "Revenir en haut": "Back to top",
    "Menu": "Menu",
    "Fermer": "Close",
    "Précédent": "Previous",
    "Suivant": "Next",
    "Image précédente": "Previous image",
    "Image suivante": "Next image",
    "Témoignage précédent": "Previous testimonial",
    "Témoignage suivant": "Next testimonial",
    "Localisation OAK International School — Akpakpa, Cotonou": "OAK International School location — Akpakpa, Cotonou"
  };

  // Dictionnaire inverse (anglais -> français) pour le retour en FR
  var DICT_REV = {};
  Object.keys(DICT).forEach(function (k) {
    DICT_REV[norm(DICT[k])] = k;
  });

  var STORAGE_KEY = "siteLang";
  var originals = new WeakMap(); // node/attr owner -> Map(attrName -> original text)

  function getMap(owner) {
    if (!originals.has(owner)) originals.set(owner, {});
    return originals.get(owner);
  }

  function translateText(text, lang) {
    var key = norm(text);
    if (!key) return null;
    if (lang === "en") {
      return DICT.hasOwnProperty(key) ? DICT[key] : null;
    } else {
      return DICT_REV.hasOwnProperty(key) ? DICT_REV[key] : null;
    }
  }

  function applyToTextNodes(root, lang) {
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: function (node) {
        var p = node.parentElement;
        if (!p) return NodeFilter.FILTER_REJECT;
        var tag = p.tagName;
        if (tag === "SCRIPT" || tag === "STYLE" || tag === "NOSCRIPT") return NodeFilter.FILTER_REJECT;
        if (p.closest(".notranslate")) return NodeFilter.FILTER_REJECT;
        if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      }
    });
    var nodes = [];
    var n;
    while ((n = walker.nextNode())) nodes.push(n);

    nodes.forEach(function (node) {
      var map = getMap(node);
      if (map.fr === undefined) map.fr = node.nodeValue;
      if (lang === "en") {
        var translated = translateText(map.fr, "en");
        if (translated !== null) {
          var lead = map.fr.match(/^\s*/)[0];
          var trail = map.fr.match(/\s*$/)[0];
          node.nodeValue = lead + translated + trail;
        }
      } else {
        node.nodeValue = map.fr;
      }
    });
  }

  var ATTR_LIST = ["placeholder", "title", "aria-label"];

  function applyToAttributes(root, lang) {
    ATTR_LIST.forEach(function (attr) {
      root.querySelectorAll("[" + attr + "]").forEach(function (el) {
        if (el.closest(".notranslate")) return;
        var map = getMap(el);
        if (map[attr] === undefined) map[attr] = el.getAttribute(attr);
        if (lang === "en") {
          var translated = translateText(map[attr], "en");
          if (translated !== null) el.setAttribute(attr, translated);
        } else {
          el.setAttribute(attr, map[attr]);
        }
      });
    });
  }

  function applyLanguage(lang) {
    applyToTextNodes(document.body, lang);
    applyToAttributes(document.body, lang);
    document.documentElement.setAttribute("lang", lang);
    document.querySelectorAll(".lang-btn").forEach(function (b) {
      b.classList.toggle("active", b.getAttribute("data-lang") === lang);
    });
  }

  window.setSiteLanguage = function (lang) {
    try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) {}
    applyLanguage(lang);
  };

  document.addEventListener("DOMContentLoaded", function () {
    var saved = "fr";
    try { saved = localStorage.getItem(STORAGE_KEY) || "fr"; } catch (e) {}
    applyLanguage(saved);
  });
})();
