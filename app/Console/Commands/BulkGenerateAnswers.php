<?php

namespace App\Console\Commands;

use App\Services\AnswerBankService;
use App\Models\QuestionBank;
use Illuminate\Console\Command;

class BulkGenerateAnswers extends Command
{
    protected $signature = 'answerbank:generate
                            {--subject= : Subject to generate (physics/chemistry/biology/mathematics)}
                            {--exam= : Exam type (JEE/NEET/CBSE)}
                            {--import= : Import from JSON file}
                            {--seed : Generate seed questions for all subjects}
                            {--count=50 : Number of questions per subject for seeding}';

    protected $description = 'Pre-generate answers for common JEE/NEET questions to build the answer bank';

    protected AnswerBankService $service;

    public function __construct(AnswerBankService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        // Import from file
        if ($importFile = $this->option('import')) {
            return $this->importFromFile($importFile);
        }

        // Seed mode
        if ($this->option('seed')) {
            return $this->seedAllSubjects();
        }

        $this->error('Please specify --seed to generate seed questions or --import=file.json to import');
        return 1;
    }

    protected function importFromFile(string $filePath): int
    {
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Importing from {$filePath}...");
        $result = $this->service->bulkImport($filePath);

        $this->info("Imported: {$result['imported']} | Skipped (duplicates): {$result['skipped']}");
        return 0;
    }

    protected function seedAllSubjects(): int
    {
        $this->info("Seeding Answer Bank with common questions...");
        $this->newLine();

        $allQuestions = $this->getSeedQuestions();
        $totalProcessed = 0;
        $totalCached = 0;
        $totalNew = 0;

        foreach ($allQuestions as $subject => $questions) {
            $this->info("Processing {$subject}...");
            $bar = $this->output->createProgressBar(count($questions));
            $bar->start();

            foreach ($questions as $question) {
                // Check if already exists
                $normalized = QuestionBank::normalize($question);
                $hash = hash('sha256', $normalized);

                if (QuestionBank::where('question_hash', $hash)->exists()) {
                    $totalCached++;
                    $bar->advance();
                    continue;
                }

                try {
                    $result = $this->service->getAnswer($question, [
                        'exam_type' => 'JEE/NEET',
                    ]);

                    if ($result['source'] === 'api') {
                        $totalNew++;
                    } else {
                        $totalCached++;
                    }

                    // Rate limit - sleep 1 second between API calls
                    if ($result['source'] === 'api') {
                        sleep(1);
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("Failed: {$question} - {$e->getMessage()}");
                }

                $bar->advance();
                $totalProcessed++;
            }

            $bar->finish();
            $this->newLine(2);
        }

        $this->info("================================================");
        $this->info("Seeding Complete!");
        $this->components->twoColumnDetail('Total Processed', (string) $totalProcessed);
        $this->components->twoColumnDetail('New Answers Generated', (string) $totalNew);
        $this->components->twoColumnDetail('Already Cached', (string) $totalCached);
        $this->components->twoColumnDetail('Total in Bank', (string) QuestionBank::count());
        $this->newLine();

        return 0;
    }

    /**
     * Seed questions - most commonly asked JEE/NEET questions
     * EXPAND THIS LIST with your actual student data
     */
    protected function getSeedQuestions(): array
    {
        return [
            'Physics' => [
                // Mechanics
                "State and explain Newton's first law of motion",
                "State and explain Newton's second law of motion with formula",
                "State Newton's third law of motion with examples",
                "What is the difference between mass and weight?",
                "Derive the equations of motion v = u + at",
                "Derive s = ut + 1/2 at^2",
                "Explain projectile motion and derive range formula",
                "What is centripetal force? Derive its formula",
                "State and prove work energy theorem",
                "Explain conservation of linear momentum",
                "What is moment of inertia? Explain with examples",
                "Explain simple harmonic motion with equations",
                "Derive time period of simple pendulum",
                "State and explain Kepler's laws of planetary motion",
                "What is escape velocity? Derive its formula",

                // Thermodynamics
                "State the zeroth law of thermodynamics",
                "State first law of thermodynamics",
                "Explain second law of thermodynamics",
                "What is Carnot engine? Derive its efficiency",
                "Explain the difference between isothermal and adiabatic process",
                "What is entropy? Explain with examples",

                // Waves & Optics
                "What is the difference between transverse and longitudinal waves?",
                "State and explain Snell's law of refraction",
                "Derive lens maker's formula",
                "Explain Young's double slit experiment",
                "What is diffraction of light?",
                "Explain total internal reflection with applications",

                // Electrostatics & Current
                "State and explain Coulomb's law",
                "What is electric field? Derive field due to point charge",
                "Explain Gauss's law and its applications",
                "What is electric potential? Derive potential due to point charge",
                "Explain parallel plate capacitor and derive capacitance",
                "State and explain Ohm's law",
                "Explain Kirchhoff's laws with examples",
                "What is Wheatstone bridge? Derive balance condition",
                "Explain Biot-Savart law",
                "State and explain Ampere's circuital law",
                "Explain electromagnetic induction and Faraday's law",
                "What is self inductance and mutual inductance?",

                // Modern Physics
                "Explain photoelectric effect",
                "What is de Broglie wavelength?",
                "Explain Bohr's model of hydrogen atom",
                "What is nuclear fission and fusion?",
                "Explain radioactive decay and half-life",
            ],

            'Chemistry' => [
                // Physical Chemistry
                "What is Raoult's law? Explain with formula",
                "Explain ideal gas equation PV=nRT",
                "What are colligative properties?",
                "Explain electrochemical cell and Nernst equation",
                "What is chemical equilibrium? Explain Le Chatelier's principle",
                "Explain rate of reaction and factors affecting it",
                "What is activation energy?",
                "Derive the first order rate equation",
                "Explain Hess's law of heat summation",
                "What is ionic product of water?",

                // Inorganic Chemistry
                "Explain the trends in periodic table",
                "What is hybridization? Explain sp, sp2, sp3",
                "Explain VSEPR theory",
                "What are coordination compounds?",
                "Explain crystal field theory",
                "What is the difference between ionic and covalent bonds?",
                "Explain hydrogen bonding with examples",
                "What are d and f block elements?",
                "Explain extraction of metals - thermodynamic principles",
                "What are interhalogen compounds?",

                // Organic Chemistry
                "What is IUPAC nomenclature? Explain with examples",
                "Explain SN1 and SN2 reaction mechanism",
                "What is Markovnikov's rule?",
                "Explain the difference between electrophile and nucleophile",
                "What are aromatic compounds? Explain Huckel's rule",
                "Explain Friedel Crafts alkylation and acylation",
                "What are carboxylic acids? Explain their properties",
                "Explain the preparation and properties of alcohols",
                "What are amines? Classify them",
                "Explain aldol condensation reaction",
                "What is polymerization? Types of polymers",
                "Explain optical isomerism with examples",
            ],

            'Biology' => [
                // Cell Biology
                "Explain the structure of a plant cell with diagram",
                "What is the difference between prokaryotic and eukaryotic cells?",
                "Explain the structure and function of mitochondria",
                "What is cell cycle? Explain its phases",
                "Explain mitosis with its stages",
                "Explain meiosis and its significance",
                "What is the structure and function of cell membrane?",
                "Explain active and passive transport across cell membrane",
                "What are enzymes? Explain lock and key model",

                // Genetics
                "State Mendel's laws of inheritance",
                "Explain the structure of DNA double helix",
                "What is the central dogma of molecular biology?",
                "Explain DNA replication",
                "What is transcription? Explain the process",
                "Explain translation/protein synthesis",
                "What are mutations? Types and examples",
                "Explain sex-linked inheritance with examples",
                "What is genetic code? List its properties",
                "Explain PCR technique and its applications",

                // Human Physiology
                "Explain the mechanism of breathing",
                "What is the structure of heart? Explain cardiac cycle",
                "Explain the process of digestion in humans",
                "What is excretory system? Explain structure of nephron",
                "Explain the mechanism of nerve impulse transmission",
                "What are hormones? Explain endocrine glands and their functions",
                "Explain the human immune system",
                "What is the difference between humoral and cell-mediated immunity?",

                // Plant Biology
                "Explain photosynthesis with light and dark reactions",
                "What is transpiration? Types and factors affecting it",
                "Explain plant growth regulators (auxin, gibberellin, cytokinin)",
                "What is nitrogen cycle?",
                "Explain the process of nitrogen fixation",

                // Ecology & Evolution
                "Explain the concept of ecological succession",
                "What is the food web? Explain energy flow in ecosystem",
                "Explain natural selection and Darwinism",
                "What are the causes of biodiversity loss?",
                "Explain the greenhouse effect and global warming",
            ],

            'Mathematics' => [
                // Algebra
                "Solve quadratic equations using quadratic formula",
                "What is arithmetic progression? Find nth term and sum",
                "What is geometric progression? Find nth term and sum",
                "Explain binomial theorem and find general term",
                "What is permutation and combination? Explain with formulas",
                "Explain mathematical induction with example",
                "What are complex numbers? Explain Argand diagram",
                "How to find inverse of a 3x3 matrix?",
                "Explain Cramer's rule for solving linear equations",
                "What are determinants? Properties of determinants",

                // Calculus
                "What is limit of a function? Explain L'Hopital's rule",
                "Explain differentiation from first principles",
                "What are the rules of differentiation (product, quotient, chain)?",
                "Explain integration by parts",
                "Explain integration by partial fractions",
                "What is definite integral? Explain fundamental theorem of calculus",
                "Explain maxima and minima of functions",
                "What is the application of derivatives in rate of change?",
                "Explain area under curve using integration",
                "What are differential equations? Solve first order ODE",

                // Trigonometry
                "Prove the trigonometric identities sin^2 + cos^2 = 1",
                "Explain trigonometric ratios of compound angles",
                "What are inverse trigonometric functions?",
                "Explain the sine rule and cosine rule",
                "How to solve trigonometric equations?",

                // Coordinate Geometry
                "Find the equation of a straight line in different forms",
                "What is the equation of a circle?",
                "Explain the equation and properties of parabola",
                "Explain the equation and properties of ellipse",
                "What is the equation of hyperbola?",
                "How to find distance between two points in 3D?",

                // Probability & Statistics
                "What is Bayes theorem? Explain with example",
                "Explain mean, median, mode and standard deviation",
                "What is conditional probability?",
                "Explain random variables and probability distribution",
                "What is binomial distribution?",
            ],
        ];
    }
}
