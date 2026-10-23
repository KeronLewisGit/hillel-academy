<?php

/*
|--------------------------------------------------------------------------
| Hillel Academy school facts
|--------------------------------------------------------------------------
| Public copy that is not edited from the dashboard. Sources (captured October
| 2026): the school's own site hillelacademyjm.com (about-us, mission-and-values,
| admissions-package, admissions-package-grades7-9, national-standards-curriculum,
| imyc, igcse, ibdp, high-school, prep-school, university-admissions, contact-us),
| its 2026-27 tuition PDFs (dated 01/04/2026) and 2026-27 calendar PDF, the IB
| school directory (code 003311, DP authorised 2008), archived hilleljm.com pages
| (founding story, campus) and the Jamaica Gleaner. Nothing here is invented;
| things we could not confirm (crest, official colours, current Director) are
| left out. Phones, emails, fees and entry years are office-editable Settings.
*/

return [
    // Concept preview on a temporary domain: banner + noindex. Turn off at launch.
    'preview' => (bool) env('HILLEL_PREVIEW', false),
    // Demo only: show family sign-in links on screen. NEVER on a live site.
    'demo_links' => (bool) env('HILLEL_DEMO_LINKS', false),
    // Fallback office inbox when the Settings value is empty.
    'notify' => env('HILLEL_NOTIFY_EMAIL'),

    'name' => 'Hillel Academy',
    'tagline' => 'International school in Kingston, Jamaica',
    'meta_description' => 'Hillel Academy, Kingston: an independent, not-for-profit international school since 1969. K1 to Grade 13, IMYC, Cambridge IGCSE and the IB Diploma. Apply online.',
    'footer_line' => 'An independent, non-denominational, not-for-profit international school in the foothills of the Blue Mountains since 1969.',
    'legal_line' => 'Learning for Living.',
    'founded' => 1969,
    'motto' => 'Learning for Living',

    'address' => ['street' => '51 Upper Mark Way', 'city' => 'Kingston 8', 'region' => 'St. Andrew', 'country' => 'JM', 'country_name' => 'Jamaica'],
    'map_url' => 'https://www.openstreetmap.org/search?query=51%20Upper%20Mark%20Way%2C%20Kingston%208%2C%20Jamaica',
    // Linked from the school's own website.
    'social' => [
        'facebook' => 'https://www.facebook.com/HillelAcademyJM',
        'instagram' => 'https://www.instagram.com/hillelacademyjm',
    ],
    // Direct lines from hillelacademyjm.com/contact-us (shown on the contact page).
    'other_phones' => [
        'Prep School office' => '876-905-3351',
        'High School office' => '876-905-3352',
    ],
    'tuition_email' => 'tuition@gohillel.com',

    // The school's two sections (used for vacancies and the entry chart).
    'sections' => [
        'prep' => ['name' => 'Prep School', 'grades' => 'K1 – Grade 6', 'ages' => '3 – 11'],
        'high' => ['name' => 'High School', 'grades' => 'Grades 7 – 13', 'ages' => '11 – 19'],
    ],

    // Typical age = the age a child turns by 31 August of the year they start,
    // following the school's K1 rule (third birthday on or before 31 August).
    'grades' => [
        'k1' => ['label' => 'K1', 'section' => 'prep', 'age' => 3],
        'k2' => ['label' => 'K2', 'section' => 'prep', 'age' => 4],
        'g1' => ['label' => 'Grade 1', 'section' => 'prep', 'age' => 5],
        'g2' => ['label' => 'Grade 2', 'section' => 'prep', 'age' => 6],
        'g3' => ['label' => 'Grade 3', 'section' => 'prep', 'age' => 7],
        'g4' => ['label' => 'Grade 4', 'section' => 'prep', 'age' => 8],
        'g5' => ['label' => 'Grade 5', 'section' => 'prep', 'age' => 9],
        'g6' => ['label' => 'Grade 6', 'section' => 'prep', 'age' => 10],
        'g7' => ['label' => 'Grade 7', 'section' => 'high', 'age' => 11],
        'g8' => ['label' => 'Grade 8', 'section' => 'high', 'age' => 12],
        'g9' => ['label' => 'Grade 9', 'section' => 'high', 'age' => 13],
        'g10' => ['label' => 'Grade 10', 'section' => 'high', 'age' => 14],
        'g11' => ['label' => 'Grade 11', 'section' => 'high', 'age' => 15],
        'g12' => ['label' => 'Grade 12', 'section' => 'high', 'age' => 16],
        'g13' => ['label' => 'Grade 13', 'section' => 'high', 'age' => 17],
    ],
    // Kindergarten applicants have no school reports yet.
    'no_reports_grades' => ['k1', 'k2'],
    'age_note' => 'Children entering K1 must have had their third birthday on or before 31 August of the year they start. Ages for other grades follow from that rule and are a guide; the Admissions Office confirms placement.',

    // Defaults for office-editable settings (Dashboard → Settings overrides them).
    'settings' => [
        'phone_main' => '876-925-1980',
        'phone_admissions' => '876-941-2912',
        'email_admissions' => 'admissions@gohillel.com',
        'email_careers' => null,
        'office_hours' => null,
        'fees_year' => '2026/27',
        'fees_note' => 'Three terms a year. A one-time, non-refundable enrolment fee is paid when a family accepts the offer of a place: US$1,750 for Jamaican students and US$3,500 for international students in K1 to Grade 11, and US$875 for Grades 12 and 13. Families with three or four children at Hillel receive a 10% tuition discount on the youngest child (three children) or the youngest two (four children).',
        'fees_source' => 'Source: Hillel Academy 2026–2027 tuition schedules for Jamaican and international students (dated 1 April 2026). The school may adjust fees and exchange rates.',
        'entry_years' => '2026/27, 2027/28',
        'applications_open' => '1',
        'offer_days' => '14',
        'notify_emails' => null,
        'assessment_location' => 'Hillel Academy, 51 Upper Mark Way',
    ],

    // Published 2026–27 tuition per term (FeeSeeder loads these into an empty table).
    'fees' => [
        ['label' => 'K1', 'grades' => 'Kindergarten 1', 'amount_jmd' => 364000, 'amount_usd' => 3760],
        ['label' => 'K2', 'grades' => 'Kindergarten 2', 'amount_jmd' => 396000, 'amount_usd' => 3980],
        ['label' => 'Grades 1 – 2', 'grades' => 'Prep School', 'amount_jmd' => 489000, 'amount_usd' => 4530],
        ['label' => 'Grades 3 – 4', 'grades' => 'Prep School', 'amount_jmd' => 506000, 'amount_usd' => 4620],
        ['label' => 'Grades 5 – 6', 'grades' => 'Prep School', 'amount_jmd' => 521000, 'amount_usd' => 4720],
        ['label' => 'Grade 7', 'grades' => 'High School', 'amount_jmd' => 564000, 'amount_usd' => 5040],
        ['label' => 'Grades 8 – 9', 'grades' => 'High School', 'amount_jmd' => 639000, 'amount_usd' => 5440],
        ['label' => 'Grades 10 – 11', 'grades' => 'High School · IGCSE', 'amount_jmd' => 688000, 'amount_usd' => 5740],
        ['label' => 'Grades 12 – 13', 'grades' => 'Sixth Form · IB Diploma', 'amount_jmd' => 736000, 'amount_usd' => 6020],
    ],

    'hero' => [
        'note' => 'Kingston, Jamaica · Since 1969',
        'title' => 'Learning for living, in the foothills of the Blue Mountains',
        'lead' => 'Hillel Academy is an independent, not-for-profit international school for about 800 children from more than 40 countries, from K1 to Grade 13.',
    ],

    'facts' => [
        ['tab' => 'Founded', 'value' => '1969', 'label' => 'Independent, not-for-profit, non-denominational'],
        ['tab' => 'Students', 'value' => '~800', 'label' => 'From more than 40 countries'],
        ['tab' => 'Programmes', 'value' => 'IMYC · IGCSE · IB', 'label' => 'IB World School, Diploma since 2008'],
        ['tab' => 'Accredited', 'value' => 'NEASC', 'label' => 'Authorised by Cambridge and the IB'],
    ],

    // The learning journey shown on the home and academics pages.
    'stages' => [
        'prep' => [
            'name' => 'Prep School', 'grades' => 'K1 – Grade 6', 'ages' => 'Ages 3 – 11', 'photo' => 'prep-classroom',
            'summary' => 'Kindergarten from age three, then Jamaica’s National Standards Curriculum, with Spanish from K1.',
            'detail' => [
                'Children join K1 at three. In K1 and K2 they begin to learn the fundamentals of literacy, numeracy, problem solving, knowledge of self and the world, and social skills and responsibilities through a structured, inter-disciplinary and age-appropriate approach.',
                'Prep School follows the National Standards Curriculum for Grades 1 to 3 and 4 to 6, which focuses on applying knowledge and skills to solve problems in familiar and unfamiliar situations.',
                'Pupils build the skills of collaboration, communication and creative and critical thinking, and are prepared for the Ministry of Education’s standardised assessments, including the Primary Exit Profile (PEP). Spanish is taught from K1 to Grade 6, and art, drama and physical education run throughout.',
            ],
        ],
        'middle' => [
            'name' => 'Middle School', 'grades' => 'Grades 7 – 9', 'ages' => 'Ages 11 – 14', 'photo' => 'sports-day',
            'summary' => 'The International Middle Years Curriculum (IMYC), which the school says only Hillel offers in Jamaica.',
            'detail' => [
                'Grades 7 to 9 follow the International Middle Years Curriculum, a concept-focused, inquiry-based curriculum designed for 11 to 14 year olds and the move from primary to secondary school.',
                'It is built around clearly stated learning goals, international mindedness and both independent and collaborative work.',
            ],
        ],
        'igcse' => [
            'name' => 'Cambridge IGCSE', 'grades' => 'Grades 10 – 11', 'ages' => 'Ages 14 – 16', 'photo' => 'drama',
            'summary' => 'A two-year Cambridge programme and the Grade 11 external examination at Hillel.',
            'detail' => [
                'From Grade 10 (fourth form), students take the Cambridge International General Certificate of Secondary Education over two years. It is the only Grade 11 external examination offered at Hillel.',
                'Learner-centred, enquiry-based IGCSE courses build the foundations for the IB Diploma in Grades 12 and 13.',
            ],
        ],
        'sixth' => [
            'name' => 'IB Diploma', 'grades' => 'Grades 12 – 13', 'ages' => 'Ages 16 – 19', 'photo' => 'ib-classroom',
            'summary' => 'The International Baccalaureate Diploma Programme and Courses in the Sixth Form.',
            'detail' => [
                'Hillel has been an IB World School offering the Diploma Programme since 2008. The two-year programme of study for 16 to 19 year olds, including Creativity, Activity and Service, prepares students for university and the world of work.',
                'Students get help with careers and university applications at school, including PSAT and SAT preparation and an annual College Fair the school says features over 60 universities.',
            ],
        ],
    ],
    // The three cards on the home page.
    'home_stages' => ['prep', 'middle', 'sixth'],

    'place' => [
        'photo' => 'campus-aerial',
        'title' => 'Thirteen acres in the hills above Kingston',
        'body' => [
            'Hillel’s campus on Upper Mark Way sits in the foothills of the Blue Mountains, with playing fields, a pool and tennis courts among the classrooms.',
            'The school was founded in 1969 by the United Congregation of Israelites in Jamaica and is non-denominational: families of every faith and from more than 40 countries learn together.',
        ],
    ],

    'admissions_steps' => [
        ['title' => 'Visit or enquire', 'text' => 'Book a campus tour online, or send the Admissions Office your questions.'],
        ['title' => 'Apply online', 'text' => 'Five short steps, saved as you go. Upload documents as scans or phone photos.'],
        ['title' => 'Assessment', 'text' => 'Once documents are checked, choose an assessment time in the family portal.'],
        ['title' => 'Decision', 'text' => 'See the decision in the portal and accept a place online.'],
    ],

    'process' => [
        ['title' => 'Enquire or visit', 'text' => 'Send an enquiry or book a campus tour. The Admissions Office will tell you how places look for your child’s grade.'],
        ['title' => 'Start online', 'text' => 'Give your name, email and your child’s grade. We email a secure link to the family portal; there is no password to remember.'],
        ['title' => 'Complete the application', 'text' => 'Student details, parents and guardians, schooling, documents, then review and submit. You get a reference number straight away.'],
        ['title' => 'Documents are checked', 'text' => 'The Admissions Office reviews each document. If one needs resending, the portal tells you why.'],
        ['title' => 'Assessment', 'text' => 'When documents are complete, pick an assessment time in the portal. What the assessment involves depends on the grade (see below).'],
        ['title' => 'Decision and offer', 'text' => 'The decision appears in the portal and by email. Accept a place online before the reply date. If a grade is full, your child can join the waiting list.'],
    ],

    // How the school assesses applicants, by grade (hillelacademyjm.com admissions packages).
    'assessment_by_grade' => [
        ['grades' => 'K1 and K2', 'text' => 'Children spend part of a day at school for informal group activities between March and May before a September start. Staff look at social maturity, motor skills and early literacy and numeracy readiness. Parents are told the date at least two weeks ahead.'],
        ['grades' => 'Grades 1 – 6', 'text' => 'An individual assessment in English and Mathematics, which can be arranged at any time of year, plus report cards from the previous school(s) for up to three years.'],
        ['grades' => 'Grade 7', 'text' => 'An Information Evening with a tour by Student Ambassadors, then an Entrance Examination in English, Mathematics and Non-Verbal Reasoning in April or May. An early-admission sitting is held in late January or early February. Three years of reports are required.'],
        ['grades' => 'Grades 8 – 11', 'text' => 'A day at school with age-appropriate assessments in English, Mathematics and Non-Verbal Reasoning, which can be arranged at any time of year. After that and three years of reports, an interview with the Principal.'],
        ['grades' => 'Sixth Form (IB)', 'text' => 'An IB Information Evening and a tour by Student Ambassadors, followed by an interview.'],
    ],

    'about' => [
        'photo' => 'campus-buildings',
        'lead' => 'The largest international school in Jamaica, in its own words: independent, co-educational, multicultural and non-denominational, and run as a private not-for-profit institution.',
        'history' => [
            'Hillel Academy was founded in 1969 by the United Congregation of Israelites in Jamaica. The Gleaner credits Rabbi Bernard Hooker and Eli Matalon as its founders. The school began with 11 students in Rabbi Hooker’s house on Oxford Road in New Kingston.',
            'Today about 800 children from more than 40 countries study on a campus of around 13 acres on Upper Mark Way, in the foothills of the Blue Mountains, from Kindergarten 1 to Grade 13.',
            'Hillel became an IB World School, authorised for the Diploma Programme, in 2008. It is accredited by NEASC and authorised by Cambridge and the International Baccalaureate.',
        ],
        'vision' => 'Hillel, a leading educational institution, where diversity thrives and learners are provided with a holistic educational experience.',
        'mission' => 'To inspire well-rounded, problem-solving, lifelong learners and confident global citizens prepared to change the world.',
        'values' => [
            ['letter' => 'P', 'name' => 'Personal Responsibility', 'text' => 'for our words and actions'],
            ['letter' => 'R', 'name' => 'Respect', 'text' => 'for ourselves, others and the environment'],
            ['letter' => 'I', 'name' => 'Integrity', 'text' => 'doing what is fair and right'],
            ['letter' => 'D', 'name' => 'Determination', 'text' => 'always giving and doing our best'],
            ['letter' => 'E', 'name' => 'Empathy', 'text' => 'being caring, kind and considerate of others'],
        ],
        'facts' => [
            'Founded' => '1969',
            'Motto' => 'Learning for Living',
            'Students' => 'About 800, from more than 40 countries',
            'Grades' => 'K1 to Grade 13',
            'Accreditation' => 'NEASC; authorised by Cambridge and the IB',
            'Campus' => 'About 13 acres, 51 Upper Mark Way, Kingston 8',
        ],
        'gallery' => ['campus-aerial', 'sports-day'],
    ],

    'academics' => [
        'photo' => 'ib-classroom',
        'lead' => 'One campus from K1 to Grade 13: Jamaica’s National Standards Curriculum in the Prep School, then the IMYC, Cambridge IGCSE and the IB Diploma in the High School.',
    ],

    'admissions_photo' => 'campus-aerial',

    'school_life' => [
        'photo' => 'sports-day',
        'lead' => 'Every student takes part in at least two co-curricular activities each term: one sport, and one arts, service or creative activity.',
        // From the school's own Prep School and High School "Life" pages.
        'groups' => [
            ['icon' => 'ball', 'title' => 'Prep School sport', 'items' => ['Tae-Kwon Do', 'Basketball', 'Swimming', 'Cricket', 'Golf', 'Lawn tennis', 'Table tennis', 'Pony Club (horseback riding)', 'Polo', 'Track & field', 'Football']],
            ['icon' => 'palette', 'title' => 'Prep School service & creative', 'items' => ['Brownies', 'Cub Scouts', 'Art Club', 'Chess', 'Drumming', 'Guitar', 'Choir', 'Ensemble', 'Dance Club', 'Environmental Club', 'Writers Inc', 'Science Club', 'Lego Yuh Mind (robotics)', 'Star Club (Jewish club)', 'Circle Square (Bible club)', 'French', 'Spell Well', 'Ballet', 'Drama Club']],
            ['icon' => 'ball', 'title' => 'High School sport', 'items' => ['Swimming', 'Football', 'Basketball', 'Cricket', 'Lawn tennis', 'Golf', 'Clay shooting', 'Pony Club (horseback riding)', 'Polo', 'Tae-Kwon Do', 'Water polo', 'Track & field', 'Table tennis', 'Volleyball']],
            ['icon' => 'music', 'title' => 'High School arts, interest & service', 'items' => ['Debating', 'Choir', 'Jazz', 'Wind band', 'Robotics', 'Jewelry Making Club', 'Environmental Club', 'Junior Achievement', 'Angels of Love', 'Science & Technology Club', 'Computer Club', 'Drama', 'H.E.A.L. (Help Earth and Life)', 'Key Club', 'Drumming', 'Modern Languages Club', 'Art', 'Art & craft', 'Dance', 'Investment Club', 'Photography', 'Astronomy', 'Global Information Network', 'Cheer Club', 'Etiquette Club']],
        ],
        'houses' => [
            ['name' => 'Sinai', 'colour' => 'Blue', 'hex' => '#2f5fb3'],
            ['name' => 'Masada', 'colour' => 'Red', 'hex' => '#c43a31'],
            ['name' => 'Maccabees', 'colour' => 'Green', 'hex' => '#2e7d4f'],
            ['name' => 'Carmel', 'colour' => 'Yellow', 'hex' => '#e8b31c'],
        ],
        'traditions' => [
            'Hillel is the home of the Panthers.',
            'The four houses compete for the Chairman’s Inter-house Trophy.',
            'The PTA supports the HEART Fund Run, a 1K and 3K race, Sports Day and Teachers’ Day.',
            'The calendar includes Founders Day, the Carol and Hanukkah Hour, sports days, a road race, prizegivings and the book fair.',
        ],
        'gallery' => ['football', 'drama'],
    ],

    'careers' => [
        'photo' => 'campus-buildings',
    ],

    // Photos in public/img ({key}-{480,960,1920}.webp). Credits in config/photo-credits.php.
    // "ours" = published by Hillel Academy itself on hillelacademyjm.com.
    'photos' => [
        'campus-aerial' => ['w' => 1600, 'h' => 800, 'credit' => 'hillel-aerial', 'ours' => true, 'alt' => 'Aerial view of the Hillel Academy campus: playing fields, a pool and tennis courts among the trees', 'tab' => 'The campus, Upper Mark Way'],
        'campus-buildings' => ['w' => 1440, 'h' => 955, 'credit' => 'hillel-students', 'ours' => true, 'alt' => 'Students spell out HILLEL on the field in front of the school buildings, with green hills behind', 'tab' => 'Kingston 8'],
        'sports-day' => ['w' => 1920, 'h' => 1285, 'credit' => 'hillel-running', 'ours' => true, 'alt' => 'Students racing across the field at a Hillel sports event', 'tab' => 'Sports day'],
        'prep-classroom' => ['w' => 500, 'h' => 436, 'credit' => 'hillel-kids', 'ours' => true, 'alt' => 'Young pupils practising handwriting at a shared table', 'tab' => 'Prep School'],
        'ib-classroom' => ['w' => 1000, 'h' => 368, 'credit' => 'hillel-ib', 'ours' => true, 'alt' => 'Sixth Form students working on laptops with their teacher', 'tab' => 'Sixth Form'],
        'football' => ['w' => 842, 'h' => 531, 'credit' => 'hillel-football', 'ours' => true, 'alt' => 'The Hillel Academy Manning Cup football team, 2025', 'tab' => 'Manning Cup squad, 2025'],
        'drama' => ['w' => 746, 'h' => 498, 'credit' => 'hillel-drama', 'ours' => true, 'alt' => 'Students in costume on stage during a school drama production', 'tab' => 'Drama production'],
        'blue-mountains' => ['w' => 1920, 'h' => 1280, 'credit' => 'bjcm', 'ours' => false, 'alt' => 'Forested slopes of the Blue Mountains under cloud', 'tab' => 'The Blue Mountains'],
    ],

    // First administrator, created by `php artisan db:seed` (or setup.php) when both are set.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
