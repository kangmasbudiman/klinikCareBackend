<?php

namespace Database\Seeders;

use App\Models\IcdCode;
use Illuminate\Database\Seeder;

class IcdCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ICD-10 Codes (Diagnosis)
        $icd10Codes = [
            // Chapter I: Certain infectious and parasitic diseases (A00-B99)
            [
                'code' => 'A09',
                'name_id' => 'Diare dan gastroenteritis yang diduga berasal dari infeksi',
                'name_en' => 'Infectious gastroenteritis and colitis, unspecified',
                'chapter' => 'I',
                'chapter_name' => 'Certain infectious and parasitic diseases',
                'block' => 'A00-A09',
                'block_name' => 'Intestinal infectious diseases',
            ],
            [
                'code' => 'A15.0',
                'name_id' => 'Tuberkulosis paru, dikonfirmasi secara mikroskopis',
                'name_en' => 'Tuberculosis of lung, confirmed by sputum microscopy',
                'chapter' => 'I',
                'chapter_name' => 'Certain infectious and parasitic diseases',
                'block' => 'A15-A19',
                'block_name' => 'Tuberculosis',
            ],
            [
                'code' => 'A90',
                'name_id' => 'Demam dengue [dengue klasik]',
                'name_en' => 'Dengue fever [classical dengue]',
                'chapter' => 'I',
                'chapter_name' => 'Certain infectious and parasitic diseases',
                'block' => 'A90-A99',
                'block_name' => 'Arthropod-borne viral fevers and viral haemorrhagic fevers',
            ],
            [
                'code' => 'A91',
                'name_id' => 'Demam berdarah dengue',
                'name_en' => 'Dengue haemorrhagic fever',
                'chapter' => 'I',
                'chapter_name' => 'Certain infectious and parasitic diseases',
                'block' => 'A90-A99',
                'block_name' => 'Arthropod-borne viral fevers and viral haemorrhagic fevers',
            ],
            [
                'code' => 'B34.9',
                'name_id' => 'Infeksi virus, tidak spesifik',
                'name_en' => 'Viral infection, unspecified',
                'chapter' => 'I',
                'chapter_name' => 'Certain infectious and parasitic diseases',
                'block' => 'B25-B34',
                'block_name' => 'Other viral diseases',
            ],

            // Chapter IV: Endocrine, nutritional and metabolic diseases (E00-E89)
            [
                'code' => 'E10',
                'name_id' => 'Diabetes melitus tipe 1',
                'name_en' => 'Type 1 diabetes mellitus',
                'chapter' => 'IV',
                'chapter_name' => 'Endocrine, nutritional and metabolic diseases',
                'block' => 'E10-E14',
                'block_name' => 'Diabetes mellitus',
            ],
            [
                'code' => 'E11',
                'name_id' => 'Diabetes melitus tipe 2',
                'name_en' => 'Type 2 diabetes mellitus',
                'chapter' => 'IV',
                'chapter_name' => 'Endocrine, nutritional and metabolic diseases',
                'block' => 'E10-E14',
                'block_name' => 'Diabetes mellitus',
            ],
            [
                'code' => 'E78.0',
                'name_id' => 'Hiperkolesterolemia murni',
                'name_en' => 'Pure hypercholesterolaemia',
                'chapter' => 'IV',
                'chapter_name' => 'Endocrine, nutritional and metabolic diseases',
                'block' => 'E70-E88',
                'block_name' => 'Metabolic disorders',
            ],
            [
                'code' => 'E79.0',
                'name_id' => 'Hiperurisemia tanpa tanda artritis dan penyakit tofaseus',
                'name_en' => 'Hyperuricaemia without signs of inflammatory arthritis and tophaceous disease',
                'chapter' => 'IV',
                'chapter_name' => 'Endocrine, nutritional and metabolic diseases',
                'block' => 'E70-E88',
                'block_name' => 'Metabolic disorders',
            ],

            // Chapter VI: Diseases of the nervous system (G00-G99)
            [
                'code' => 'G43.9',
                'name_id' => 'Migrain, tidak spesifik',
                'name_en' => 'Migraine, unspecified',
                'chapter' => 'VI',
                'chapter_name' => 'Diseases of the nervous system',
                'block' => 'G40-G47',
                'block_name' => 'Episodic and paroxysmal disorders',
            ],
            [
                'code' => 'G44.2',
                'name_id' => 'Sakit kepala tipe tegang',
                'name_en' => 'Tension-type headache',
                'chapter' => 'VI',
                'chapter_name' => 'Diseases of the nervous system',
                'block' => 'G40-G47',
                'block_name' => 'Episodic and paroxysmal disorders',
            ],

            // Chapter VII: Diseases of the eye (H00-H59)
            [
                'code' => 'H10.9',
                'name_id' => 'Konjungtivitis, tidak spesifik',
                'name_en' => 'Conjunctivitis, unspecified',
                'chapter' => 'VII',
                'chapter_name' => 'Diseases of the eye and adnexa',
                'block' => 'H10-H13',
                'block_name' => 'Disorders of conjunctiva',
            ],

            // Chapter VIII: Diseases of the ear (H60-H95)
            [
                'code' => 'H66.9',
                'name_id' => 'Otitis media, tidak spesifik',
                'name_en' => 'Otitis media, unspecified',
                'chapter' => 'VIII',
                'chapter_name' => 'Diseases of the ear and mastoid process',
                'block' => 'H65-H75',
                'block_name' => 'Diseases of middle ear and mastoid',
            ],

            // Chapter IX: Diseases of the circulatory system (I00-I99)
            [
                'code' => 'I10',
                'name_id' => 'Hipertensi esensial (primer)',
                'name_en' => 'Essential (primary) hypertension',
                'chapter' => 'IX',
                'chapter_name' => 'Diseases of the circulatory system',
                'block' => 'I10-I15',
                'block_name' => 'Hypertensive diseases',
            ],
            [
                'code' => 'I11.9',
                'name_id' => 'Penyakit jantung hipertensi tanpa gagal jantung',
                'name_en' => 'Hypertensive heart disease without heart failure',
                'chapter' => 'IX',
                'chapter_name' => 'Diseases of the circulatory system',
                'block' => 'I10-I15',
                'block_name' => 'Hypertensive diseases',
            ],
            [
                'code' => 'I25.9',
                'name_id' => 'Penyakit jantung iskemik kronis, tidak spesifik',
                'name_en' => 'Chronic ischaemic heart disease, unspecified',
                'chapter' => 'IX',
                'chapter_name' => 'Diseases of the circulatory system',
                'block' => 'I20-I25',
                'block_name' => 'Ischaemic heart diseases',
            ],

            // Chapter X: Diseases of the respiratory system (J00-J99)
            [
                'code' => 'J00',
                'name_id' => 'Nasofaringitis akut [common cold]',
                'name_en' => 'Acute nasopharyngitis [common cold]',
                'chapter' => 'X',
                'chapter_name' => 'Diseases of the respiratory system',
                'block' => 'J00-J06',
                'block_name' => 'Acute upper respiratory infections',
            ],
            [
                'code' => 'J02.9',
                'name_id' => 'Faringitis akut, tidak spesifik',
                'name_en' => 'Acute pharyngitis, unspecified',
                'chapter' => 'X',
                'chapter_name' => 'Diseases of the respiratory system',
                'block' => 'J00-J06',
                'block_name' => 'Acute upper respiratory infections',
            ],
            [
                'code' => 'J03.9',
                'name_id' => 'Tonsilitis akut, tidak spesifik',
                'name_en' => 'Acute tonsillitis, unspecified',
                'chapter' => 'X',
                'chapter_name' => 'Diseases of the respiratory system',
                'block' => 'J00-J06',
                'block_name' => 'Acute upper respiratory infections',
            ],
            [
                'code' => 'J06.9',
                'name_id' => 'Infeksi saluran pernapasan atas akut, tidak spesifik',
                'name_en' => 'Acute upper respiratory infection, unspecified',
                'chapter' => 'X',
                'chapter_name' => 'Diseases of the respiratory system',
                'block' => 'J00-J06',
                'block_name' => 'Acute upper respiratory infections',
            ],
            [
                'code' => 'J18.9',
                'name_id' => 'Pneumonia, tidak spesifik',
                'name_en' => 'Pneumonia, unspecified',
                'chapter' => 'X',
                'chapter_name' => 'Diseases of the respiratory system',
                'block' => 'J09-J18',
                'block_name' => 'Influenza and pneumonia',
            ],
            [
                'code' => 'J20.9',
                'name_id' => 'Bronkitis akut, tidak spesifik',
                'name_en' => 'Acute bronchitis, unspecified',
                'chapter' => 'X',
                'chapter_name' => 'Diseases of the respiratory system',
                'block' => 'J20-J22',
                'block_name' => 'Other acute lower respiratory infections',
            ],
            [
                'code' => 'J30.4',
                'name_id' => 'Rinitis alergi, tidak spesifik',
                'name_en' => 'Allergic rhinitis, unspecified',
                'chapter' => 'X',
                'chapter_name' => 'Diseases of the respiratory system',
                'block' => 'J30-J39',
                'block_name' => 'Other diseases of upper respiratory tract',
            ],
            [
                'code' => 'J45.9',
                'name_id' => 'Asma, tidak spesifik',
                'name_en' => 'Asthma, unspecified',
                'chapter' => 'X',
                'chapter_name' => 'Diseases of the respiratory system',
                'block' => 'J40-J47',
                'block_name' => 'Chronic lower respiratory diseases',
            ],

            // Chapter XI: Diseases of the digestive system (K00-K93)
            [
                'code' => 'K02.9',
                'name_id' => 'Karies gigi, tidak spesifik',
                'name_en' => 'Dental caries, unspecified',
                'chapter' => 'XI',
                'chapter_name' => 'Diseases of the digestive system',
                'block' => 'K00-K14',
                'block_name' => 'Diseases of oral cavity, salivary glands and jaws',
            ],
            [
                'code' => 'K04.7',
                'name_id' => 'Abses periapikal tanpa sinus',
                'name_en' => 'Periapical abscess without sinus',
                'chapter' => 'XI',
                'chapter_name' => 'Diseases of the digestive system',
                'block' => 'K00-K14',
                'block_name' => 'Diseases of oral cavity, salivary glands and jaws',
            ],
            [
                'code' => 'K29.7',
                'name_id' => 'Gastritis, tidak spesifik',
                'name_en' => 'Gastritis, unspecified',
                'chapter' => 'XI',
                'chapter_name' => 'Diseases of the digestive system',
                'block' => 'K20-K31',
                'block_name' => 'Diseases of oesophagus, stomach and duodenum',
            ],
            [
                'code' => 'K30',
                'name_id' => 'Dispepsia fungsional',
                'name_en' => 'Functional dyspepsia',
                'chapter' => 'XI',
                'chapter_name' => 'Diseases of the digestive system',
                'block' => 'K20-K31',
                'block_name' => 'Diseases of oesophagus, stomach and duodenum',
            ],
            [
                'code' => 'K59.0',
                'name_id' => 'Konstipasi',
                'name_en' => 'Constipation',
                'chapter' => 'XI',
                'chapter_name' => 'Diseases of the digestive system',
                'block' => 'K55-K63',
                'block_name' => 'Other diseases of intestines',
            ],

            // Chapter XII: Diseases of the skin (L00-L99)
            [
                'code' => 'L20.9',
                'name_id' => 'Dermatitis atopik, tidak spesifik',
                'name_en' => 'Atopic dermatitis, unspecified',
                'chapter' => 'XII',
                'chapter_name' => 'Diseases of the skin and subcutaneous tissue',
                'block' => 'L20-L30',
                'block_name' => 'Dermatitis and eczema',
            ],
            [
                'code' => 'L23.9',
                'name_id' => 'Dermatitis kontak alergi, tidak spesifik',
                'name_en' => 'Allergic contact dermatitis, unspecified cause',
                'chapter' => 'XII',
                'chapter_name' => 'Diseases of the skin and subcutaneous tissue',
                'block' => 'L20-L30',
                'block_name' => 'Dermatitis and eczema',
            ],
            [
                'code' => 'L50.9',
                'name_id' => 'Urtikaria, tidak spesifik',
                'name_en' => 'Urticaria, unspecified',
                'chapter' => 'XII',
                'chapter_name' => 'Diseases of the skin and subcutaneous tissue',
                'block' => 'L50-L54',
                'block_name' => 'Urticaria and erythema',
            ],

            // Chapter XIII: Diseases of the musculoskeletal system (M00-M99)
            [
                'code' => 'M10.9',
                'name_id' => 'Gout, tidak spesifik',
                'name_en' => 'Gout, unspecified',
                'chapter' => 'XIII',
                'chapter_name' => 'Diseases of the musculoskeletal system and connective tissue',
                'block' => 'M05-M14',
                'block_name' => 'Inflammatory polyarthropathies',
            ],
            [
                'code' => 'M54.5',
                'name_id' => 'Nyeri punggung bawah',
                'name_en' => 'Low back pain',
                'chapter' => 'XIII',
                'chapter_name' => 'Diseases of the musculoskeletal system and connective tissue',
                'block' => 'M50-M54',
                'block_name' => 'Other dorsopathies',
            ],
            [
                'code' => 'M79.3',
                'name_id' => 'Panniculitis, tidak spesifik',
                'name_en' => 'Panniculitis, unspecified',
                'chapter' => 'XIII',
                'chapter_name' => 'Diseases of the musculoskeletal system and connective tissue',
                'block' => 'M70-M79',
                'block_name' => 'Other soft tissue disorders',
            ],

            // Chapter XIV: Diseases of the genitourinary system (N00-N99)
            [
                'code' => 'N39.0',
                'name_id' => 'Infeksi saluran kemih, lokasi tidak spesifik',
                'name_en' => 'Urinary tract infection, site not specified',
                'chapter' => 'XIV',
                'chapter_name' => 'Diseases of the genitourinary system',
                'block' => 'N30-N39',
                'block_name' => 'Other diseases of urinary system',
            ],

            // Chapter XV: Pregnancy, childbirth and the puerperium (O00-O99)
            [
                'code' => 'O80',
                'name_id' => 'Persalinan spontan tunggal',
                'name_en' => 'Single spontaneous delivery',
                'chapter' => 'XV',
                'chapter_name' => 'Pregnancy, childbirth and the puerperium',
                'block' => 'O80-O84',
                'block_name' => 'Delivery',
            ],
            [
                'code' => 'Z34.0',
                'name_id' => 'Pemantauan kehamilan normal pertama',
                'name_en' => 'Supervision of normal first pregnancy',
                'chapter' => 'XXI',
                'chapter_name' => 'Factors influencing health status and contact with health services',
                'block' => 'Z30-Z39',
                'block_name' => 'Persons encountering health services in circumstances related to reproduction',
            ],

            // Chapter XVIII: Symptoms, signs (R00-R99)
            [
                'code' => 'R05',
                'name_id' => 'Batuk',
                'name_en' => 'Cough',
                'chapter' => 'XVIII',
                'chapter_name' => 'Symptoms, signs and abnormal clinical and laboratory findings, not elsewhere classified',
                'block' => 'R00-R09',
                'block_name' => 'Symptoms and signs involving the circulatory and respiratory systems',
            ],
            [
                'code' => 'R10.4',
                'name_id' => 'Nyeri abdomen lainnya dan yang tidak spesifik',
                'name_en' => 'Other and unspecified abdominal pain',
                'chapter' => 'XVIII',
                'chapter_name' => 'Symptoms, signs and abnormal clinical and laboratory findings, not elsewhere classified',
                'block' => 'R10-R19',
                'block_name' => 'Symptoms and signs involving the digestive system and abdomen',
            ],
            [
                'code' => 'R11',
                'name_id' => 'Mual dan muntah',
                'name_en' => 'Nausea and vomiting',
                'chapter' => 'XVIII',
                'chapter_name' => 'Symptoms, signs and abnormal clinical and laboratory findings, not elsewhere classified',
                'block' => 'R10-R19',
                'block_name' => 'Symptoms and signs involving the digestive system and abdomen',
            ],
            [
                'code' => 'R50.9',
                'name_id' => 'Demam, tidak spesifik',
                'name_en' => 'Fever, unspecified',
                'chapter' => 'XVIII',
                'chapter_name' => 'Symptoms, signs and abnormal clinical and laboratory findings, not elsewhere classified',
                'block' => 'R50-R69',
                'block_name' => 'General symptoms and signs',
            ],
            [
                'code' => 'R51',
                'name_id' => 'Sakit kepala',
                'name_en' => 'Headache',
                'chapter' => 'XVIII',
                'chapter_name' => 'Symptoms, signs and abnormal clinical and laboratory findings, not elsewhere classified',
                'block' => 'R50-R69',
                'block_name' => 'General symptoms and signs',
            ],

            // Chapter XIX: Injury, poisoning (S00-T98)
            [
                'code' => 'S00.9',
                'name_id' => 'Cedera superfisial kepala, tidak spesifik',
                'name_en' => 'Superficial injury of head, unspecified',
                'chapter' => 'XIX',
                'chapter_name' => 'Injury, poisoning and certain other consequences of external causes',
                'block' => 'S00-S09',
                'block_name' => 'Injuries to the head',
            ],
            [
                'code' => 'T14.0',
                'name_id' => 'Cedera superfisial pada daerah tubuh yang tidak spesifik',
                'name_en' => 'Superficial injury of unspecified body region',
                'chapter' => 'XIX',
                'chapter_name' => 'Injury, poisoning and certain other consequences of external causes',
                'block' => 'T08-T14',
                'block_name' => 'Injuries to unspecified part of trunk, limb or body region',
            ],

            // Chapter XXI: Factors influencing health status (Z00-Z99)
            [
                'code' => 'Z00.0',
                'name_id' => 'Pemeriksaan kesehatan umum',
                'name_en' => 'General medical examination',
                'chapter' => 'XXI',
                'chapter_name' => 'Factors influencing health status and contact with health services',
                'block' => 'Z00-Z13',
                'block_name' => 'Persons encountering health services for examination and investigation',
            ],
            [
                'code' => 'Z01.4',
                'name_id' => 'Pemeriksaan ginekologi (umum) (rutin)',
                'name_en' => 'Gynecological examination (general) (routine)',
                'chapter' => 'XXI',
                'chapter_name' => 'Factors influencing health status and contact with health services',
                'block' => 'Z00-Z13',
                'block_name' => 'Persons encountering health services for examination and investigation',
            ],
            [
                'code' => 'Z23',
                'name_id' => 'Perlu imunisasi terhadap penyakit bakteri tunggal',
                'name_en' => 'Need for immunization against single bacterial diseases',
                'chapter' => 'XXI',
                'chapter_name' => 'Factors influencing health status and contact with health services',
                'block' => 'Z20-Z29',
                'block_name' => 'Persons with potential health hazards related to communicable diseases',
            ],
        ];

        // ICD-9-CM Codes (Procedures)
        $icd9cmCodes = [
            // Diagnostic procedures
            [
                'code' => '89.52',
                'name_id' => 'Elektrokardiogram',
                'name_en' => 'Electrocardiogram',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '89',
                'block_name' => 'Interview, Evaluation, Consultation, and Examination',
            ],
            [
                'code' => '88.72',
                'name_id' => 'Ultrasonografi diagnostik abdomen',
                'name_en' => 'Diagnostic ultrasound of abdomen',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '88',
                'block_name' => 'Other Diagnostic Radiology and Related Techniques',
            ],
            [
                'code' => '88.78',
                'name_id' => 'Ultrasonografi diagnostik kebidanan',
                'name_en' => 'Diagnostic ultrasound of gravid uterus',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '88',
                'block_name' => 'Other Diagnostic Radiology and Related Techniques',
            ],
            [
                'code' => '87.44',
                'name_id' => 'Rontgen dada rutin',
                'name_en' => 'Routine chest x-ray',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '87',
                'block_name' => 'Diagnostic Radiology',
            ],

            // Laboratory procedures
            [
                'code' => '90.59',
                'name_id' => 'Pemeriksaan darah lainnya',
                'name_en' => 'Other microscopic examination of blood',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '90',
                'block_name' => 'Microscopic Examination',
            ],
            [
                'code' => '91.39',
                'name_id' => 'Pemeriksaan urine lainnya',
                'name_en' => 'Other microscopic examination of specimen from urinary system',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '91',
                'block_name' => 'Microscopic Examination - II',
            ],

            // Therapeutic procedures
            [
                'code' => '93.94',
                'name_id' => 'Inhalasi oksigen medis',
                'name_en' => 'Respiratory medication administered by nebulizer',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '93',
                'block_name' => 'Physical Therapy, Respiratory Therapy, Rehabilitation',
            ],
            [
                'code' => '99.21',
                'name_id' => 'Injeksi antibiotik',
                'name_en' => 'Injection of antibiotic',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '99',
                'block_name' => 'Other Nonoperative Procedures',
            ],
            [
                'code' => '99.23',
                'name_id' => 'Injeksi steroid',
                'name_en' => 'Injection of steroid',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '99',
                'block_name' => 'Other Nonoperative Procedures',
            ],
            [
                'code' => '99.29',
                'name_id' => 'Injeksi atau infus zat terapeutik atau profilaksis lainnya',
                'name_en' => 'Injection or infusion of other therapeutic or prophylactic substance',
                'chapter' => '16',
                'chapter_name' => 'Miscellaneous Diagnostic and Therapeutic Procedures',
                'block' => '99',
                'block_name' => 'Other Nonoperative Procedures',
            ],

            // Dental procedures
            [
                'code' => '23.01',
                'name_id' => 'Ekstraksi gigi desidua',
                'name_en' => 'Extraction of deciduous tooth',
                'chapter' => '6',
                'chapter_name' => 'Operations on the Digestive System',
                'block' => '23',
                'block_name' => 'Removal and Restoration of Teeth',
            ],
            [
                'code' => '23.09',
                'name_id' => 'Ekstraksi gigi lainnya',
                'name_en' => 'Extraction of other tooth',
                'chapter' => '6',
                'chapter_name' => 'Operations on the Digestive System',
                'block' => '23',
                'block_name' => 'Removal and Restoration of Teeth',
            ],
            [
                'code' => '23.2',
                'name_id' => 'Restorasi gigi dengan penambalan',
                'name_en' => 'Restoration of tooth by filling',
                'chapter' => '6',
                'chapter_name' => 'Operations on the Digestive System',
                'block' => '23',
                'block_name' => 'Removal and Restoration of Teeth',
            ],

            // Wound care
            [
                'code' => '86.22',
                'name_id' => 'Debridemen eksisi luka',
                'name_en' => 'Excisional debridement of wound, infection, or burn',
                'chapter' => '15',
                'chapter_name' => 'Operations on the Integumentary System',
                'block' => '86',
                'block_name' => 'Operations on Skin and Subcutaneous Tissue',
            ],
            [
                'code' => '86.59',
                'name_id' => 'Penjahitan kulit dan jaringan subkutan lokasi lain',
                'name_en' => 'Suture of skin and subcutaneous tissue of other sites',
                'chapter' => '15',
                'chapter_name' => 'Operations on the Integumentary System',
                'block' => '86',
                'block_name' => 'Operations on Skin and Subcutaneous Tissue',
            ],

            // IV procedures
            [
                'code' => '38.93',
                'name_id' => 'Venous catheterization, not elsewhere classified',
                'name_en' => 'Venous catheterization, not elsewhere classified',
                'chapter' => '7',
                'chapter_name' => 'Operations on the Cardiovascular System',
                'block' => '38',
                'block_name' => 'Incision, Excision, and Occlusion of Vessels',
            ],

            // Obstetric procedures
            [
                'code' => '72.0',
                'name_id' => 'Ekstraksi forsep rendah',
                'name_en' => 'Low forceps operation',
                'chapter' => '13',
                'chapter_name' => 'Obstetrical Procedures',
                'block' => '72',
                'block_name' => 'Forceps, Vacuum, and Breech Delivery',
            ],
            [
                'code' => '73.59',
                'name_id' => 'Persalinan normal dengan bantuan manual lainnya',
                'name_en' => 'Other manually assisted delivery',
                'chapter' => '13',
                'chapter_name' => 'Obstetrical Procedures',
                'block' => '73',
                'block_name' => 'Other Procedures Inducing or Assisting Delivery',
            ],
            [
                'code' => '75.34',
                'name_id' => 'Pemantauan janin lainnya',
                'name_en' => 'Other fetal monitoring',
                'chapter' => '13',
                'chapter_name' => 'Obstetrical Procedures',
                'block' => '75',
                'block_name' => 'Obstetric Operations',
            ],
        ];

        // Insert ICD-10 codes
        foreach ($icd10Codes as $code) {
            IcdCode::create(array_merge($code, [
                'type' => IcdCode::TYPE_ICD10,
                'is_bpjs_claimable' => true,
                'is_active' => true,
            ]));
        }

        // Insert ICD-9-CM codes
        foreach ($icd9cmCodes as $code) {
            IcdCode::create(array_merge($code, [
                'type' => IcdCode::TYPE_ICD9CM,
                'is_bpjs_claimable' => true,
                'is_active' => true,
            ]));
        }
    }
}
