<?php

namespace App\Entity;

class ConstantsClass
{
    // Valeur de la note pour annuler le coefficient
    public const UNRANKED_MARK = 0.1;

    // Mot de passe par defaut
    public const DEFAULT_TEACHER_PASSWORD = 'ens';

    // Les groupes des matières pour l'enseignement général
    public const CATEGORY1 = 'Scientifique';
    public const CATEGORY2 = 'Littéraire';
    public const CATEGORY3 = 'Humaine';

    // // Les differents niveaux de l'enseignament général
    public const LEVEL_1 = '6ème';
    public const LEVEL_2 = '5ème';
    public const LEVEL_3 = '4ème';
    public const LEVEL_4 = '3ème';
    public const LEVEL_5 = '2nde';
    public const LEVEL_6 = '1ère';
    public const LEVEL_7 = 'Tle';

    public const FROM_1 = 'From 1';
    public const FROM_2 = 'From 2';
    public const FROM_3 = 'From 3';
    public const FROM_4 = 'From 4';
    public const FROM_5 = 'From 5';
    public const LOWER_6 = 'Lower 6';
    public const UPPER_6 = 'Upper 6';
    
    // Les groupes des matières pour l'enseignement TECHNIQUE
    // public const CATEGORY1 = 'Professionnelle';
    // public const CATEGORY2 = 'Générale';
    // public const CATEGORY3 = 'Complémentaire';
    
    //Les differents niveaux de l'enseignament Technique
    // public const LEVEL_1 = '1ère année';
    // public const LEVEL_2 = '2ème année';
    // public const LEVEL_3 = '3ème année';
    // public const LEVEL_4 = '4ème année';
    // public const LEVEL_5 = '2nde';
    // public const LEVEL_6 = '1ère';
    // public const LEVEL_7 = 'Tle';

    // Les différentes poste ou responsabilités
    public const TEACHER_DUTY = 'ENSEIGNANT';
    public const SUPERVISOR_DUTY = 'SURVEILLANT GENERAL';
    public const CENSOR_DUTY = 'CENSEUR';
    public const HEADMASTER_DUTY = 'PROVISEUR';
    public const DIRECTOR_DUTY = 'DIRECTEUR';
    public const COUNSELLOR_DUTY = 'CONSEILLER';
    public const TREASURER_DUTY = 'INTENDANT';
    public const SECRETARY_DUTY = 'SECRETAIRE';
    public const ECONOME_DUTY = 'ECONOME';
    public const ACCOUNTER_DUTY = 'COMPTABLE';
    public const SOCIAL_DUTY = 'ACTION SOCIALE';
    public const CHIEF_ORIENTATION_DUTY = 'CHEF SERVICE ORIENTATION';
    public const APPS_DUTY = 'RESPONSABLE APPS';
    public const SPORT_SERVICE_DUTY = 'CHEF SERVICE DES SPORTS SCOLAIRE';
    public const OTHER_DUTY = '-AUTRE-';
    public const AP_DUTY = 'ANIMATEUR PEDAGOGIQUE';
    public const CHIEF_WORK_DUTY = 'CHEF DES TRAVAUX';
    public const SUPPORT_STAFF = "PERSONNEL D'APPUI";
    public const PERSONNEL_ADMINISTRATIF = 'PERSONNEL ADMINISTRATIF';


    // Département des corps qui ne sont pas des enseignants
    public const OTHERS_DEPARTMENT = '-AUTRE-';

    // Grade de vacataire
    public const VAC_GRADE = 'VAC';

    // Situation matrimonaile
    public const MARRIED = 'M';
    public const SINGLE = 'C';
    public const WIDOW= 'V';
    public const DIVORCED= 'D';

    // Differents rôles en fonction du poste occupé
    public const ROLE_TEACHER = 'ROLE_ENSEIGNANT';
    public const ROLE_SUPERVISOR = 'ROLE_SURVEILLANT_GENERAL';
    public const ROLE_CENSOR = 'ROLE_CENSEUR';
    public const ROLE_HEADMASTER = 'ROLE_PROVISEUR';
    public const ROLE_DIRECTOR = 'ROLE_DIRECTOR';
    public const ROLE_COUNSELLOR = 'ROLE_CONSEILLER';
    public const ROLE_TREASURER = 'ROLE_INTENDANT';
    public const ROLE_SECRETARY = 'ROLE_SECRETAIRE';
    public const ROLE_ECONOME = 'ROLE_ECONOME';
    public const ROLE_ACCOUNTER= 'ROLE_ACCOUNTER';
    public const ROLE_SOCIAL = 'ROLE_SOCIAL';
    public const ROLE_MEDICAL = 'ROLE_MEDICAL';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const ROLE_CHEF_TRAVAUX = 'ROLE_CHEF_TRAVAUX';
    public const ROLE_CHEF_SS = 'ROLE_CHEF_SS';
    public const ROLE_CHEF_SERVICE_DES_SPORTS_SCOLAIRE = 'ROLE_CHEF_SERVICE_DES_SPORTS_SCOLAIRE';

    // Responsabilité des chefs et délégués de classe
    public const RESPONSABILITY_KING_1 = 'CHEF';
    public const RESPONSABILITY_KING_2 = 'SOUS CHEF';
    public const RESPONSABILITY_DELEGATE_1 = 'DELEGUE 1';
    public const RESPONSABILITY_DELEGATE_2 = 'DELEGUE 2';
    public const RESPONSABILITY_DEFAULT = 'AUCUNE';
    public const NOT_KING_1 = 'PAS DE CHEF DE CLASSE';


    // Les cycles
    public const CYCLE_1 = 'Cycle 1';
    public const CYCLE_2 = 'Cycle 2';
    public const SCHOOL_SUMMARY = 'Etablissement';
    public const SCHOOL = 'Etablishment';

    // Matière de base et autre
    public const SUBJECT_FIRST_GROUP = 'Matière de base';
    public const SUBJECT_SECOND_GROUP = 'Autre';

    // Note manquante et élève manquant
    public const MISSED_MARK = 'unrecorded mark';
    public const NO_STUDENT = 'no student';

    // Les décisions possibles lors des conseils de classe
    public const DECISION_PASSED = 'Admis';
    public const DECISION_REAPETED = 'Redouble';
    public const DECISION_EXPELLED = 'Exclu';
    public const DECISION_CATCHUPPED = 'Rattrapage';
    public const DECISION_REAPETED_IF_FAILED = "Redouble si échec";
    public const DECISION_EXPELLED_IF_FAILED = "Exclu si échec";
    public const DECISION_RESIGNED = "Démissionnaire";
    public const DECISION_FINISHED = "Terminé(e)";

    // MESSAGE AU CAS OU ILN'YA PAS D'ELEVES DANS CES DECISIONS
    public const NOT_PASSED = 'AUCUN ADMIS ENREGISTRES';
    public const NOT_REAPETED = 'AUCUN REDOUBLANT ENREGISTRE';
    public const NOT_EXPELLED = 'AUCUN EXCLU ENREGISTRE';
    public const NOT_CATCHUPPED = "AUCUN ELEVE N'EST AU RATTRAPAGE";
    public const NOT_REAPETED_IF_FAILED = "AUCUN ELEVE NE REDOUBLE EN CAS D'ECHEC";
    public const NOT_EXPELLED_IF_FAILED = "AUCUN ELEVE N'EST EXCLU EN CAS D'ECHEC";
    public const NOT_RESIGNED = "AUCUN ELEVE N'A DEMISSIONNE";
    public const NOT_FINISHED = "AUCUN ELEVE N'A TERMINE";

    // Redoublant ou non
    public const REPEATER_YES = 'Oui';
    public const REPEATER_NO = 'Non';

    // HANDICAPE
    public const HANDICAPED_YES = 'Handicapé(e)';
    public const HANDICAPED_NO = 'Non handicapé(e)';

    ///SEXE
    public const SEX_M = 'M';
    public const SEX_F = 'F';

    // Les non classés
    public const UNRANKED_RANK = 'N.C';
    public const UNRANKED_AVERAGE = -1;
    public const UNRANKED_RANK_DB = -1;
    
    // l'annuel est représenté comme le trimestre 0
    public const ANNUEL_TERM = 0;

    // la fin d'année est représenté comme le trimestre 4
    public const END_YEAR = 4;

    // Quota des heures d'absence donnant droit aux censions
    public const WARNING_BAHAVIOUR = 6;
    public const BLAME_BAHAVIOUR = 10;
    public const EXCLUSION_3_DAYS = 15;
    public const EXCLUSION_5_DAYS = 19;
    public const EXCLUSION_8_DAYS = 26;
    public const DISCIPLINARY_COMMITEE = 30;

    // Quota des moyennes pour gratification ou blâme
    public const ROLL_OF_HONOUR = 12;
    public const ENCOURAGEMENT = 14;
    public const CONGRATULATION = 16;
    public const WARNING_WORK = 8;
    public const BLAME_WORK = 6;

    // Nombre de semaines par trimestre
    public const WEEKS_PER_TERM = 12;

    // Constantes à configurer en fonction de l'établissement scolaire
    public const SERVICE_NOTE = 'MINESEC/DRES-CE/DDES-MF/L.B.ODZA';
    // public const SERVICE_NOTE = 'MINESEC/DRES-EST/DDES-LD/L.BOULI';
    public const UNRANKED_COEFFICIENT = 5;

    // Type d'enseignement
    public const GENERAL_EDUCATION = "Général";
    public const TECHNICAL_EDUCATION = "Technique";

    // MOVEMENT
    public const MOVEMENT_NOSO = "Déplacé(e) Nord-Ouest / Sud-Ouest";
    public const MOVEMENT_REFFUGIE = "Réfugié(e)";


    public const SCHOOL_FEES1 = 7500; 
    public const SCHOOL_FEES2 = 10000;
    public const APEE_FEES1 = 16000;
    public const APEE_FEES2 = 16000;
    public const COMPUTER_FEES1 = 5000;
    public const COMPUTER_FEES2 = 5000;
    public const MEDICAL_BOOKLET_FEES = 1000;
    public const CLEAN_SCHOOL_FEES = 2000;
    public const PHOTO_FEES = 1000;

    public const EXAM_FEES3EME = 3500;
    public const EXAM_FEES1ERE = 9500;
    public const EXAM_FEESTLE = 10500;

    public const STAMP_FEES3EME = 1000;
    public const STAMP_FEES1ERE = 1500;
    public const STAMP_FEESTLE = 2000;

    ////RURIQUE ///
    public const APEE = 'APEE';
    public const COMPUTER = 'FRAIS INFORMATIQUE';
    public const CLEAN_SCHOOL = 'CLEAN SHOOL';
    public const MEDICAL_BOOKLET = 'LIVRET MEDICAL';
    public const PHOTO = 'PHOTO';
    public const STAMP = 'TIMBRE';

    public const LUNDI = 'Lundi/Monday';
    public const MARDI = 'Mardi/Tuesday';
    public const MERCREDI = 'Mercredi/Wednesday';
    public const JEUDI = 'Jeudi/Thursday';
    public const VENDREDI = 'Vendredi/Friday';

    //////GRADES
	public const PCEG = 'PCEG';
	public const PLEG = 'PLEG';
	public const EPPS = 'EPPS';
	public const CPO = 'CPO';
	public const CO = 'CO';
	public const MPEPS = 'MPEPS';
	public const PEPS = 'PEPS'; 
	public const IET = 'IET';
	public const CC = 'CC';
	public const PENI = 'PENI';
	public const VAC = 'VAC';
	public const PLEGHE = 'PLEG / H.E';


    //////////
    public const MODE_ADMISSION_CONCOURS = 'CONCOURS';
    public const MODE_ADMISSION_PERMUTATION = 'PERMUTATION';
    public const MODE_ADMISSION_TRANSFERT = 'TRANSFERT';
    public const MODE_ADMISSION_RECRUTEMENT = 'RECRUTEMENT';


    ///////////////APPRECIATION

    public const UNRANK = '//';
    public const FAIBLE = 'Faible';
    public const INSUFFISANT = 'Insuffisant';
    public const MEDIOCRE = 'Médiocre';
    public const PASSABLE = 'Passable';
    public const ASSEZ_BIEN = 'Assez Bien';
    public const BIEN = 'Bien';
    public const TRES_BIEN = 'Très Bien';
    public const EXCELLENT = 'Excellent';
    public const PARFAIT = 'Parfait';


    /////
    public const WEAK = 'Weak';
    public const INSUFFICIENT = 'Insufficient';
    public const POOR = 'Poor';
    public const FAIR = 'Fair';
    public const PRETTY_GOOD = 'Pretty good';
    public const GOOD = 'Good';
    public const ALRIGHT = 'Algriht';
    public const EXCELENT = 'Excellent';
    public const PERFECT = 'Perfect';

    /////APRECIATION FRANCOPHONE
    public const CTBA = 'CTBA';
    public const CBA = 'CBA';
    public const CA_FR = 'CA';
    public const CMA = 'CMA';
    public const CNA_FR = 'CNA';

    //////APRECIATION ANGLOPHONE
    public const CVWA = 'CVWA';
    public const CWA = 'CWA';
    public const CA_EN = 'CA';
    public const CAA = 'CAA';
    public const CNA_EN = 'CNA';

    //////////sub system
    
    public const ANGLOPHONE = 'Anglophone / English Speaker';
    public const FRANCOPHONE = 'Francophone / French Speaker';

    # RAS
    public const RAS = 'RAS';


}