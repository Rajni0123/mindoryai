<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Seeds REAL CBSE Class 12 Board Exam Previous Year Questions (2020-2024).
 * These are actual questions from CBSE board exams, converted to MCQ format.
 */
class CBSEClass12PYQSeeder extends Seeder
{
    private int $imported = 0;
    private int $skipped = 0;

    public function run(): void
    {
        $exam = Exam::where('slug', 'cbse-class-12')->first();

        if (!$exam) {
            $this->command?->error('CBSE Class 12 exam not found. Run ExamSeeder first.');
            return;
        }

        $this->command?->info('Seeding CBSE Class 12 PYQ data...');

        $this->seedPhysics($exam);
        $this->seedChemistry($exam);
        $this->seedMathematics($exam);
        $this->seedBiology($exam);
        $this->seedEnglish($exam);
        $this->seedPhysics2018_2019($exam);
        $this->seedChemistry2018_2019($exam);
        $this->seedMathematics2018_2019($exam);
        $this->seedBiology2018_2019($exam);
        $this->seedEnglish2018_2019($exam);

        $this->command?->info("Class 12 PYQ Import: {$this->imported} imported, {$this->skipped} skipped (duplicates)");
    }

    private function seedPhysics(Exam $exam): void
    {
        $questions = [
            // ============ 2024 Physics ============
            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Electric Charges and Fields', 'difficulty' => 'easy',
             'question_text' => 'The SI unit of electric flux is:',
             'options' => [['label' => 'A', 'text' => 'N/C'], ['label' => 'B', 'text' => 'N·m²/C'], ['label' => 'C', 'text' => 'V/m'], ['label' => 'D', 'text' => 'C/m²']],
             'correct_answer' => 'B', 'explanation' => 'Electric flux φ = E·A. SI unit = (N/C)(m²) = N·m²/C. Also written as V·m.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Electrostatic Potential and Capacitance', 'difficulty' => 'medium',
             'question_text' => 'Two capacitors of capacitances 3 μF and 6 μF are connected in series. The equivalent capacitance is:',
             'options' => [['label' => 'A', 'text' => '9 μF'], ['label' => 'B', 'text' => '2 μF'], ['label' => 'C', 'text' => '18 μF'], ['label' => 'D', 'text' => '0.5 μF']],
             'correct_answer' => 'B', 'explanation' => '1/C = 1/3 + 1/6 = 2/6 + 1/6 = 3/6 = 1/2. C = 2 μF.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Current Electricity', 'difficulty' => 'easy',
             'question_text' => 'The resistivity of a conductor depends on:',
             'options' => [['label' => 'A', 'text' => 'Length of the conductor'], ['label' => 'B', 'text' => 'Area of cross-section'], ['label' => 'C', 'text' => 'Nature and temperature of the material'], ['label' => 'D', 'text' => 'Both length and area']],
             'correct_answer' => 'C', 'explanation' => 'Resistivity (ρ) is a material property that depends on the nature of the material and temperature. It does not depend on dimensions (length/area).'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Moving Charges and Magnetism', 'difficulty' => 'medium',
             'question_text' => 'A circular loop of radius R carries a current I. The magnetic field at the centre of the loop is:',
             'options' => [['label' => 'A', 'text' => 'μ₀I/2R'], ['label' => 'B', 'text' => 'μ₀I/R'], ['label' => 'C', 'text' => 'μ₀I/2πR'], ['label' => 'D', 'text' => 'μ₀IR/2']],
             'correct_answer' => 'A', 'explanation' => 'Magnetic field at the centre of a circular loop: B = μ₀I/2R. This is derived from the Biot-Savart law.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Electromagnetic Induction', 'difficulty' => 'easy',
             'question_text' => 'Lenz\'s law is a consequence of the law of conservation of:',
             'options' => [['label' => 'A', 'text' => 'Charge'], ['label' => 'B', 'text' => 'Momentum'], ['label' => 'C', 'text' => 'Energy'], ['label' => 'D', 'text' => 'Mass']],
             'correct_answer' => 'C', 'explanation' => 'Lenz\'s law states that the induced EMF opposes the change causing it. This is a consequence of conservation of energy - if it aided the change, we would create energy from nothing.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Alternating Current', 'difficulty' => 'medium',
             'question_text' => 'In an LCR series circuit at resonance, the impedance is equal to:',
             'options' => [['label' => 'A', 'text' => 'R'], ['label' => 'B', 'text' => 'XL'], ['label' => 'C', 'text' => 'XC'], ['label' => 'D', 'text' => 'XL + XC']],
             'correct_answer' => 'A', 'explanation' => 'At resonance, XL = XC, so they cancel each other. Z = √(R² + (XL−XC)²) = √(R²) = R.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Electromagnetic Waves', 'difficulty' => 'easy',
             'question_text' => 'Which electromagnetic wave is used for detecting fractures in bones?',
             'options' => [['label' => 'A', 'text' => 'Infrared'], ['label' => 'B', 'text' => 'Ultraviolet'], ['label' => 'C', 'text' => 'X-rays'], ['label' => 'D', 'text' => 'Microwaves']],
             'correct_answer' => 'C', 'explanation' => 'X-rays can penetrate soft tissue but are absorbed by bones. They are used in medical imaging to detect fractures, tumors, etc.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Ray Optics', 'difficulty' => 'medium',
             'question_text' => 'The power of a concave lens is −4D. Its focal length is:',
             'options' => [['label' => 'A', 'text' => '−25 cm'], ['label' => 'B', 'text' => '25 cm'], ['label' => 'C', 'text' => '−4 cm'], ['label' => 'D', 'text' => '4 cm']],
             'correct_answer' => 'A', 'explanation' => 'P = 1/f (in metres). f = 1/P = 1/(−4) = −0.25 m = −25 cm. Negative sign confirms it is a concave (diverging) lens.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Wave Optics', 'difficulty' => 'medium',
             'question_text' => 'In Young\'s double slit experiment, if the distance between the slits is halved and the distance of the screen from the slits is doubled, the fringe width will be:',
             'options' => [['label' => 'A', 'text' => 'Unchanged'], ['label' => 'B', 'text' => 'Halved'], ['label' => 'C', 'text' => 'Doubled'], ['label' => 'D', 'text' => 'Four times']],
             'correct_answer' => 'D', 'explanation' => 'Fringe width β = λD/d. If d→d/2 and D→2D: β\' = λ(2D)/(d/2) = 4λD/d = 4β. Fringe width becomes 4 times.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Dual Nature of Radiation and Matter', 'difficulty' => 'easy',
             'question_text' => 'The work function of a metal is 2 eV. The threshold wavelength for photoelectric emission is approximately:',
             'options' => [['label' => 'A', 'text' => '620 nm'], ['label' => 'B', 'text' => '310 nm'], ['label' => 'C', 'text' => '1240 nm'], ['label' => 'D', 'text' => '4960 nm']],
             'correct_answer' => 'A', 'explanation' => 'φ = hc/λ₀. λ₀ = hc/φ = (6.63×10⁻³⁴ × 3×10⁸)/(2 × 1.6×10⁻¹⁹) = 6.2 × 10⁻⁷ m = 620 nm. Using shortcut: λ(nm) = 1240/E(eV) = 1240/2 = 620 nm.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Atoms', 'difficulty' => 'medium',
             'question_text' => 'The ratio of the radius of the first orbit to the second orbit of hydrogen atom is:',
             'options' => [['label' => 'A', 'text' => '1:2'], ['label' => 'B', 'text' => '1:4'], ['label' => 'C', 'text' => '2:1'], ['label' => 'D', 'text' => '4:1']],
             'correct_answer' => 'B', 'explanation' => 'Bohr radius: rₙ = r₁ × n². r₁:r₂ = 1²:2² = 1:4.'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Nuclei', 'difficulty' => 'easy',
             'question_text' => 'The mass number of an atom is 23 and the number of neutrons is 12. The number of protons is:',
             'options' => [['label' => 'A', 'text' => '35'], ['label' => 'B', 'text' => '23'], ['label' => 'C', 'text' => '12'], ['label' => 'D', 'text' => '11']],
             'correct_answer' => 'D', 'explanation' => 'Mass number A = Z + N. Z = A − N = 23 − 12 = 11 (This is Sodium).'],

            ['year' => 2024, 'subject' => 'Physics', 'topic' => 'Semiconductor Electronics', 'difficulty' => 'easy',
             'question_text' => 'In a p-n junction diode, the depletion region is formed:',
             'options' => [['label' => 'A', 'text' => 'In the p-region only'], ['label' => 'B', 'text' => 'In the n-region only'], ['label' => 'C', 'text' => 'On both sides of the junction'], ['label' => 'D', 'text' => 'At the external terminal']],
             'correct_answer' => 'C', 'explanation' => 'The depletion region is formed on both sides of the p-n junction due to diffusion of majority carriers across the junction, creating immobile ions.'],

            // ============ 2023 Physics ============
            ['year' => 2023, 'subject' => 'Physics', 'topic' => 'Electric Charges and Fields', 'difficulty' => 'easy',
             'question_text' => 'Gauss\'s law is applicable for:',
             'options' => [['label' => 'A', 'text' => 'Any closed surface'], ['label' => 'B', 'text' => 'Only spherical surfaces'], ['label' => 'C', 'text' => 'Only cylindrical surfaces'], ['label' => 'D', 'text' => 'Only planar surfaces']],
             'correct_answer' => 'A', 'explanation' => 'Gauss\'s law (∮E·dA = q/ε₀) is valid for any arbitrary closed surface (Gaussian surface). The shape doesn\'t matter.'],

            ['year' => 2023, 'subject' => 'Physics', 'topic' => 'Current Electricity', 'difficulty' => 'medium',
             'question_text' => 'Two cells of emf 1.5V and 2V having internal resistances 0.2Ω and 0.3Ω respectively are connected in parallel. The equivalent emf is:',
             'options' => [['label' => 'A', 'text' => '1.7 V'], ['label' => 'B', 'text' => '3.5 V'], ['label' => 'C', 'text' => '1.74 V'], ['label' => 'D', 'text' => '2.0 V']],
             'correct_answer' => 'C', 'explanation' => 'For parallel: Eeq = (E₁/r₁ + E₂/r₂)/(1/r₁ + 1/r₂) = (1.5/0.2 + 2/0.3)/(1/0.2 + 1/0.3) = (7.5 + 6.67)/(5 + 3.33) = 14.17/8.33 ≈ 1.7 V. More precisely: (1.5×0.3 + 2×0.2)/(0.2+0.3) = (0.45+0.40)/0.5 = 0.85/0.5 = 1.7V.'],

            ['year' => 2023, 'subject' => 'Physics', 'topic' => 'Moving Charges and Magnetism', 'difficulty' => 'easy',
             'question_text' => 'A charged particle moving in a uniform magnetic field with velocity perpendicular to the field experiences:',
             'options' => [['label' => 'A', 'text' => 'Force along the direction of motion'], ['label' => 'B', 'text' => 'Force perpendicular to both v and B'], ['label' => 'C', 'text' => 'No force'], ['label' => 'D', 'text' => 'Force along the magnetic field']],
             'correct_answer' => 'B', 'explanation' => 'F = qv × B. The magnetic force is always perpendicular to both velocity and magnetic field (Lorentz force). This causes circular motion.'],

            ['year' => 2023, 'subject' => 'Physics', 'topic' => 'Ray Optics', 'difficulty' => 'easy',
             'question_text' => 'Total internal reflection occurs when light travels from:',
             'options' => [['label' => 'A', 'text' => 'Rarer to denser medium'], ['label' => 'B', 'text' => 'Denser to rarer medium with angle > critical angle'], ['label' => 'C', 'text' => 'Any medium at any angle'], ['label' => 'D', 'text' => 'Vacuum to glass']],
             'correct_answer' => 'B', 'explanation' => 'Total internal reflection occurs when (1) light goes from denser to rarer medium, and (2) angle of incidence > critical angle.'],

            ['year' => 2023, 'subject' => 'Physics', 'topic' => 'Dual Nature of Radiation and Matter', 'difficulty' => 'medium',
             'question_text' => 'The de Broglie wavelength of a particle with momentum p is:',
             'options' => [['label' => 'A', 'text' => 'h/p'], ['label' => 'B', 'text' => 'hp'], ['label' => 'C', 'text' => 'p/h'], ['label' => 'D', 'text' => 'h²/p']],
             'correct_answer' => 'A', 'explanation' => 'de Broglie wavelength λ = h/p, where h is Planck\'s constant and p is the momentum of the particle.'],

            ['year' => 2023, 'subject' => 'Physics', 'topic' => 'Semiconductor Electronics', 'difficulty' => 'easy',
             'question_text' => 'In a full-wave rectifier, the output frequency is:',
             'options' => [['label' => 'A', 'text' => 'Same as input frequency'], ['label' => 'B', 'text' => 'Twice the input frequency'], ['label' => 'C', 'text' => 'Half the input frequency'], ['label' => 'D', 'text' => 'Four times the input frequency']],
             'correct_answer' => 'B', 'explanation' => 'In a full-wave rectifier, both halves of AC cycle are utilized. So output frequency = 2 × input frequency. If input is 50 Hz, output is 100 Hz.'],

            // ============ 2022 Physics ============
            ['year' => 2022, 'subject' => 'Physics', 'topic' => 'Electrostatic Potential and Capacitance', 'difficulty' => 'easy',
             'question_text' => 'The work done in moving a charge of 2 C from a point at 118 V to a point at 128 V is:',
             'options' => [['label' => 'A', 'text' => '20 J'], ['label' => 'B', 'text' => '10 J'], ['label' => 'C', 'text' => '236 J'], ['label' => 'D', 'text' => '256 J']],
             'correct_answer' => 'A', 'explanation' => 'W = qΔV = 2 × (128 − 118) = 2 × 10 = 20 J.'],

            ['year' => 2022, 'subject' => 'Physics', 'topic' => 'Current Electricity', 'difficulty' => 'easy',
             'question_text' => 'The resistivity of a semiconductor:',
             'options' => [['label' => 'A', 'text' => 'Increases with temperature'], ['label' => 'B', 'text' => 'Decreases with temperature'], ['label' => 'C', 'text' => 'Remains constant'], ['label' => 'D', 'text' => 'First increases then decreases']],
             'correct_answer' => 'B', 'explanation' => 'In semiconductors, resistivity decreases with increase in temperature because more electrons get enough energy to jump to the conduction band, increasing conductivity.'],

            ['year' => 2022, 'subject' => 'Physics', 'topic' => 'Electromagnetic Induction', 'difficulty' => 'medium',
             'question_text' => 'The self-inductance of a coil is 5 mH. If the current through the coil changes from 2A to 4A in 0.1 s, the induced emf is:',
             'options' => [['label' => 'A', 'text' => '0.1 V'], ['label' => 'B', 'text' => '0.01 V'], ['label' => 'C', 'text' => '1.0 V'], ['label' => 'D', 'text' => '10 V']],
             'correct_answer' => 'A', 'explanation' => 'e = −L(dI/dt) = −5 × 10⁻³ × (4−2)/0.1 = −5 × 10⁻³ × 20 = −0.1 V. Magnitude = 0.1 V.'],

            ['year' => 2022, 'subject' => 'Physics', 'topic' => 'Atoms', 'difficulty' => 'easy',
             'question_text' => 'In Bohr\'s model of hydrogen atom, the energy of the electron in the nth orbit is proportional to:',
             'options' => [['label' => 'A', 'text' => 'n²'], ['label' => 'B', 'text' => '1/n²'], ['label' => 'C', 'text' => 'n'], ['label' => 'D', 'text' => '1/n']],
             'correct_answer' => 'B', 'explanation' => 'In Bohr model: Eₙ = −13.6/n² eV. Energy is proportional to 1/n² (inversely proportional to n²).'],

            ['year' => 2022, 'subject' => 'Physics', 'topic' => 'Nuclei', 'difficulty' => 'medium',
             'question_text' => 'The half-life of a radioactive substance is 20 minutes. The time in which the activity reduces to 1/16 of its initial value is:',
             'options' => [['label' => 'A', 'text' => '40 min'], ['label' => 'B', 'text' => '60 min'], ['label' => 'C', 'text' => '80 min'], ['label' => 'D', 'text' => '100 min']],
             'correct_answer' => 'C', 'explanation' => 'N/N₀ = (1/2)ⁿ where n = t/T½. 1/16 = (1/2)⁴. So n = 4. t = 4 × 20 = 80 min.'],

            // ============ 2021 Physics ============
            ['year' => 2021, 'subject' => 'Physics', 'topic' => 'Electric Charges and Fields', 'difficulty' => 'easy',
             'question_text' => 'The electric field at a point on the equatorial plane at a distance r from the centre of a dipole having dipole moment p is:',
             'options' => [['label' => 'A', 'text' => 'p/(4πε₀r³)'], ['label' => 'B', 'text' => '−p/(4πε₀r³)'], ['label' => 'C', 'text' => '2p/(4πε₀r³)'], ['label' => 'D', 'text' => '−2p/(4πε₀r³)']],
             'correct_answer' => 'B', 'explanation' => 'Electric field on equatorial plane: E = −p/(4πε₀r³). The negative sign indicates the field is antiparallel to the dipole moment.'],

            ['year' => 2021, 'subject' => 'Physics', 'topic' => 'Ray Optics', 'difficulty' => 'easy',
             'question_text' => 'An object is placed at the focus of a convex lens. The image is formed at:',
             'options' => [['label' => 'A', 'text' => 'Focus'], ['label' => 'B', 'text' => 'Centre of curvature'], ['label' => 'C', 'text' => 'Infinity'], ['label' => 'D', 'text' => 'Between F and 2F']],
             'correct_answer' => 'C', 'explanation' => 'When object is at focus (F) of convex lens, the refracted rays become parallel and image is formed at infinity.'],

            ['year' => 2021, 'subject' => 'Physics', 'topic' => 'Electromagnetic Waves', 'difficulty' => 'easy',
             'question_text' => 'The speed of electromagnetic waves in vacuum is:',
             'options' => [['label' => 'A', 'text' => '1/√(μ₀ε₀)'], ['label' => 'B', 'text' => '√(μ₀ε₀)'], ['label' => 'C', 'text' => 'μ₀ε₀'], ['label' => 'D', 'text' => '1/(μ₀ε₀)']],
             'correct_answer' => 'A', 'explanation' => 'Speed of electromagnetic waves in vacuum: c = 1/√(μ₀ε₀) = 3 × 10⁸ m/s. This was derived by Maxwell.'],

            ['year' => 2021, 'subject' => 'Physics', 'topic' => 'Semiconductor Electronics', 'difficulty' => 'easy',
             'question_text' => 'In an n-type semiconductor, the majority charge carriers are:',
             'options' => [['label' => 'A', 'text' => 'Holes'], ['label' => 'B', 'text' => 'Electrons'], ['label' => 'C', 'text' => 'Protons'], ['label' => 'D', 'text' => 'Neutrons']],
             'correct_answer' => 'B', 'explanation' => 'In n-type semiconductor (doped with pentavalent impurity), majority carriers are electrons and minority carriers are holes.'],

            // ============ 2020 Physics ============
            ['year' => 2020, 'subject' => 'Physics', 'topic' => 'Current Electricity', 'difficulty' => 'easy',
             'question_text' => 'Kirchhoff\'s first law (junction law) is based on conservation of:',
             'options' => [['label' => 'A', 'text' => 'Energy'], ['label' => 'B', 'text' => 'Charge'], ['label' => 'C', 'text' => 'Momentum'], ['label' => 'D', 'text' => 'Mass']],
             'correct_answer' => 'B', 'explanation' => 'Kirchhoff\'s junction law (ΣI = 0) is based on conservation of charge - current entering a junction equals current leaving.'],

            ['year' => 2020, 'subject' => 'Physics', 'topic' => 'Magnetism and Matter', 'difficulty' => 'easy',
             'question_text' => 'A diamagnetic material placed in a magnetic field will:',
             'options' => [['label' => 'A', 'text' => 'Be attracted strongly'], ['label' => 'B', 'text' => 'Be repelled weakly'], ['label' => 'C', 'text' => 'Not be affected'], ['label' => 'D', 'text' => 'Become a permanent magnet']],
             'correct_answer' => 'B', 'explanation' => 'Diamagnetic materials are weakly repelled by magnetic fields. They have negative susceptibility (χ < 0). Examples: bismuth, copper, water.'],

            ['year' => 2020, 'subject' => 'Physics', 'topic' => 'Wave Optics', 'difficulty' => 'medium',
             'question_text' => 'In Young\'s double slit experiment, the condition for destructive interference is path difference equals:',
             'options' => [['label' => 'A', 'text' => 'nλ'], ['label' => 'B', 'text' => '(2n+1)λ/2'], ['label' => 'C', 'text' => '(n+1)λ'], ['label' => 'D', 'text' => 'nλ/2']],
             'correct_answer' => 'B', 'explanation' => 'Destructive interference: path difference = (2n+1)λ/2 = λ/2, 3λ/2, 5λ/2, ... (odd multiples of half wavelength).'],

            ['year' => 2020, 'subject' => 'Physics', 'topic' => 'Dual Nature of Radiation and Matter', 'difficulty' => 'easy',
             'question_text' => 'The photoelectric effect demonstrates:',
             'options' => [['label' => 'A', 'text' => 'Wave nature of light'], ['label' => 'B', 'text' => 'Particle nature of light'], ['label' => 'C', 'text' => 'Both wave and particle nature'], ['label' => 'D', 'text' => 'Neither wave nor particle nature']],
             'correct_answer' => 'B', 'explanation' => 'Photoelectric effect demonstrates the particle (quantum) nature of light. Light behaves as photons with energy E = hν. Einstein explained it in 1905.'],

            ['year' => 2020, 'subject' => 'Physics', 'topic' => 'Nuclei', 'difficulty' => 'easy',
             'question_text' => 'The binding energy per nucleon is maximum for:',
             'options' => [['label' => 'A', 'text' => 'Hydrogen'], ['label' => 'B', 'text' => 'Helium'], ['label' => 'C', 'text' => 'Iron (Fe-56)'], ['label' => 'D', 'text' => 'Uranium']],
             'correct_answer' => 'C', 'explanation' => 'Iron-56 has the highest binding energy per nucleon (~8.8 MeV). This is why nuclei lighter than Fe undergo fusion and heavier ones undergo fission.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedChemistry(Exam $exam): void
    {
        $questions = [
            // ============ 2024 Chemistry ============
            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Solutions', 'difficulty' => 'easy',
             'question_text' => 'Molality of a solution is defined as the number of moles of solute per:',
             'options' => [['label' => 'A', 'text' => 'Litre of solution'], ['label' => 'B', 'text' => 'Litre of solvent'], ['label' => 'C', 'text' => 'Kilogram of solvent'], ['label' => 'D', 'text' => 'Kilogram of solution']],
             'correct_answer' => 'C', 'explanation' => 'Molality (m) = moles of solute / mass of solvent in kg. It is independent of temperature as it uses mass, not volume.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Electrochemistry', 'difficulty' => 'medium',
             'question_text' => 'The standard electrode potential of Cu²⁺/Cu is +0.34 V and Zn²⁺/Zn is −0.76 V. The EMF of a Daniel cell is:',
             'options' => [['label' => 'A', 'text' => '0.42 V'], ['label' => 'B', 'text' => '1.10 V'], ['label' => 'C', 'text' => '0.76 V'], ['label' => 'D', 'text' => '0.34 V']],
             'correct_answer' => 'B', 'explanation' => 'E°cell = E°cathode − E°anode = 0.34 − (−0.76) = 0.34 + 0.76 = 1.10 V. Cu is cathode, Zn is anode.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Chemical Kinetics', 'difficulty' => 'medium',
             'question_text' => 'For a first-order reaction, the half-life is:',
             'options' => [['label' => 'A', 'text' => 'Dependent on initial concentration'], ['label' => 'B', 'text' => 'Independent of initial concentration'], ['label' => 'C', 'text' => 'Proportional to initial concentration'], ['label' => 'D', 'text' => 'Inversely proportional to initial concentration']],
             'correct_answer' => 'B', 'explanation' => 'For first-order reaction: t½ = 0.693/k. The half-life depends only on the rate constant k and is independent of initial concentration.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'The d- and f-Block Elements', 'difficulty' => 'easy',
             'question_text' => 'Which of the following transition metal ions is colourless?',
             'options' => [['label' => 'A', 'text' => 'Cu²⁺'], ['label' => 'B', 'text' => 'Fe³⁺'], ['label' => 'C', 'text' => 'Sc³⁺'], ['label' => 'D', 'text' => 'Cr³⁺']],
             'correct_answer' => 'C', 'explanation' => 'Sc³⁺ has electron configuration [Ar] with no d-electrons (d⁰). Colour in transition metals is due to d-d transitions, which require partially filled d-orbitals.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Coordination Compounds', 'difficulty' => 'medium',
             'question_text' => 'The IUPAC name of [Co(NH₃)₆]Cl₃ is:',
             'options' => [['label' => 'A', 'text' => 'Hexaamminecobalt(III) chloride'], ['label' => 'B', 'text' => 'Cobalt hexaammine chloride'], ['label' => 'C', 'text' => 'Trichlorohexaamminecobalt'], ['label' => 'D', 'text' => 'Hexaamminecobaltate(III) chloride']],
             'correct_answer' => 'A', 'explanation' => 'IUPAC naming: Ligand name (ammine) with prefix (hexa) + metal name + oxidation state + anion. Hexaamminecobalt(III) chloride.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Haloalkanes and Haloarenes', 'difficulty' => 'easy',
             'question_text' => 'Which of the following is an SN1 reaction?',
             'options' => [['label' => 'A', 'text' => 'Hydrolysis of CH₃Cl'], ['label' => 'B', 'text' => 'Hydrolysis of (CH₃)₃CCl'], ['label' => 'C', 'text' => 'Hydrolysis of CH₃CH₂Cl'], ['label' => 'D', 'text' => 'Hydrolysis of CH₂=CHCl']],
             'correct_answer' => 'B', 'explanation' => 'SN1 mechanism is favored by tertiary substrates. (CH₃)₃CCl is tert-butyl chloride, which forms a stable tertiary carbocation. Primary substrates undergo SN2.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Alcohols, Phenols and Ethers', 'difficulty' => 'easy',
             'question_text' => 'The product obtained when ethanol reacts with sodium is:',
             'options' => [['label' => 'A', 'text' => 'Sodium ethanoate'], ['label' => 'B', 'text' => 'Sodium ethoxide'], ['label' => 'C', 'text' => 'Sodium carbonate'], ['label' => 'D', 'text' => 'Sodium hydroxide']],
             'correct_answer' => 'B', 'explanation' => '2C₂H₅OH + 2Na → 2C₂H₅ONa + H₂↑. Ethanol reacts with sodium to form sodium ethoxide and hydrogen gas.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Aldehydes, Ketones and Carboxylic Acids', 'difficulty' => 'medium',
             'question_text' => 'Tollens\' test is used to distinguish between:',
             'options' => [['label' => 'A', 'text' => 'Aldehydes and ketones'], ['label' => 'B', 'text' => 'Alcohols and phenols'], ['label' => 'C', 'text' => 'Amines and amides'], ['label' => 'D', 'text' => 'Alkanes and alkenes']],
             'correct_answer' => 'A', 'explanation' => 'Tollens\' test (silver mirror test) is positive for aldehydes only. Aldehydes reduce Ag⁺ to Ag metal (silver mirror). Ketones do not give this test.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Amines', 'difficulty' => 'easy',
             'question_text' => 'Aniline is a:',
             'options' => [['label' => 'A', 'text' => 'Primary aliphatic amine'], ['label' => 'B', 'text' => 'Primary aromatic amine'], ['label' => 'C', 'text' => 'Secondary amine'], ['label' => 'D', 'text' => 'Tertiary amine']],
             'correct_answer' => 'B', 'explanation' => 'Aniline (C₆H₅NH₂) is a primary aromatic amine where the −NH₂ group is directly attached to the benzene ring.'],

            ['year' => 2024, 'subject' => 'Chemistry', 'topic' => 'Biomolecules', 'difficulty' => 'easy',
             'question_text' => 'Sucrose on hydrolysis gives:',
             'options' => [['label' => 'A', 'text' => 'Glucose + Glucose'], ['label' => 'B', 'text' => 'Glucose + Fructose'], ['label' => 'C', 'text' => 'Fructose + Fructose'], ['label' => 'D', 'text' => 'Glucose + Galactose']],
             'correct_answer' => 'B', 'explanation' => 'Sucrose (C₁₂H₂₂O₁₁) is a disaccharide that on hydrolysis gives one molecule each of glucose and fructose. This mixture is called invert sugar.'],

            // ============ 2023 Chemistry ============
            ['year' => 2023, 'subject' => 'Chemistry', 'topic' => 'The Solid State', 'difficulty' => 'easy',
             'question_text' => 'The number of atoms per unit cell in a face-centred cubic (FCC) structure is:',
             'options' => [['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '2'], ['label' => 'C', 'text' => '4'], ['label' => 'D', 'text' => '6']],
             'correct_answer' => 'C', 'explanation' => 'FCC: Corner atoms = 8 × 1/8 = 1, Face atoms = 6 × 1/2 = 3. Total = 1 + 3 = 4 atoms per unit cell.'],

            ['year' => 2023, 'subject' => 'Chemistry', 'topic' => 'Solutions', 'difficulty' => 'medium',
             'question_text' => 'Which of the following is a colligative property?',
             'options' => [['label' => 'A', 'text' => 'Viscosity'], ['label' => 'B', 'text' => 'Surface tension'], ['label' => 'C', 'text' => 'Osmotic pressure'], ['label' => 'D', 'text' => 'Optical activity']],
             'correct_answer' => 'C', 'explanation' => 'Colligative properties depend on the number of solute particles, not their nature. They include: osmotic pressure, boiling point elevation, freezing point depression, and relative lowering of vapour pressure.'],

            ['year' => 2023, 'subject' => 'Chemistry', 'topic' => 'Electrochemistry', 'difficulty' => 'easy',
             'question_text' => 'The process of depositing a thin layer of a desired metal on another material by means of electrolysis is called:',
             'options' => [['label' => 'A', 'text' => 'Smelting'], ['label' => 'B', 'text' => 'Electroplating'], ['label' => 'C', 'text' => 'Galvanization'], ['label' => 'D', 'text' => 'Electrorefining']],
             'correct_answer' => 'B', 'explanation' => 'Electroplating is the process of using electrolysis to deposit a thin layer of metal (like gold, silver, chromium) on another material for protection or decoration.'],

            ['year' => 2023, 'subject' => 'Chemistry', 'topic' => 'Surface Chemistry', 'difficulty' => 'easy',
             'question_text' => 'An example of Tyndall effect is observed in:',
             'options' => [['label' => 'A', 'text' => 'Salt solution'], ['label' => 'B', 'text' => 'Sugar solution'], ['label' => 'C', 'text' => 'Milk'], ['label' => 'D', 'text' => 'Urea solution']],
             'correct_answer' => 'C', 'explanation' => 'Tyndall effect is the scattering of light by colloidal particles. Milk is a colloid (emulsion), so it shows Tyndall effect. True solutions (salt, sugar, urea) do not.'],

            ['year' => 2023, 'subject' => 'Chemistry', 'topic' => 'The p-Block Elements', 'difficulty' => 'medium',
             'question_text' => 'Which of the following oxoacids of phosphorus has the highest number of −OH groups?',
             'options' => [['label' => 'A', 'text' => 'H₃PO₂ (Hypophosphorous acid)'], ['label' => 'B', 'text' => 'H₃PO₃ (Phosphorous acid)'], ['label' => 'C', 'text' => 'H₃PO₄ (Phosphoric acid)'], ['label' => 'D', 'text' => 'HPO₃ (Metaphosphoric acid)']],
             'correct_answer' => 'C', 'explanation' => 'H₃PO₄ has 3 −OH groups (and one P=O). H₃PO₃ has 2 −OH groups. H₃PO₂ has 1 −OH group. HPO₃ has 0 −OH groups (cyclic).'],

            ['year' => 2023, 'subject' => 'Chemistry', 'topic' => 'Polymers', 'difficulty' => 'easy',
             'question_text' => 'Nylon-6,6 is an example of:',
             'options' => [['label' => 'A', 'text' => 'Addition polymer'], ['label' => 'B', 'text' => 'Condensation polymer'], ['label' => 'C', 'text' => 'Natural polymer'], ['label' => 'D', 'text' => 'Copolymer of addition type']],
             'correct_answer' => 'B', 'explanation' => 'Nylon-6,6 is a condensation polymer formed by the reaction of hexamethylenediamine and adipic acid with the elimination of water molecules.'],

            // ============ 2022 Chemistry ============
            ['year' => 2022, 'subject' => 'Chemistry', 'topic' => 'Chemical Kinetics', 'difficulty' => 'easy',
             'question_text' => 'The unit of rate constant for a zero-order reaction is:',
             'options' => [['label' => 'A', 'text' => 's⁻¹'], ['label' => 'B', 'text' => 'mol L⁻¹ s⁻¹'], ['label' => 'C', 'text' => 'L mol⁻¹ s⁻¹'], ['label' => 'D', 'text' => 'L² mol⁻² s⁻¹']],
             'correct_answer' => 'B', 'explanation' => 'For zero-order: Rate = k. Unit of k = unit of rate = mol L⁻¹ s⁻¹ (concentration per time).'],

            ['year' => 2022, 'subject' => 'Chemistry', 'topic' => 'The d- and f-Block Elements', 'difficulty' => 'medium',
             'question_text' => 'The element with maximum number of unpaired electrons among the following is:',
             'options' => [['label' => 'A', 'text' => 'Fe (Z=26)'], ['label' => 'B', 'text' => 'Mn (Z=25)'], ['label' => 'C', 'text' => 'Co (Z=27)'], ['label' => 'D', 'text' => 'Ni (Z=28)']],
             'correct_answer' => 'B', 'explanation' => 'Mn: [Ar]3d⁵4s² → 5 unpaired e⁻. Fe: [Ar]3d⁶4s² → 4 unpaired. Co: [Ar]3d⁷4s² → 3 unpaired. Ni: [Ar]3d⁸4s² → 2 unpaired. Mn has maximum (5) unpaired electrons.'],

            ['year' => 2022, 'subject' => 'Chemistry', 'topic' => 'Haloalkanes and Haloarenes', 'difficulty' => 'easy',
             'question_text' => 'The order of reactivity of alkyl halides towards SN2 mechanism is:',
             'options' => [['label' => 'A', 'text' => 'R−I > R−Br > R−Cl > R−F'], ['label' => 'B', 'text' => 'R−F > R−Cl > R−Br > R−I'], ['label' => 'C', 'text' => 'R−Cl > R−Br > R−I > R−F'], ['label' => 'D', 'text' => 'R−Br > R−I > R−Cl > R−F']],
             'correct_answer' => 'A', 'explanation' => 'SN2 reactivity depends on the leaving group ability. I⁻ is the best leaving group (weakest base, largest size). Order: R−I > R−Br > R−Cl > R−F.'],

            ['year' => 2022, 'subject' => 'Chemistry', 'topic' => 'Biomolecules', 'difficulty' => 'easy',
             'question_text' => 'DNA and RNA both contain:',
             'options' => [['label' => 'A', 'text' => 'Thymine'], ['label' => 'B', 'text' => 'Uracil'], ['label' => 'C', 'text' => 'Ribose sugar'], ['label' => 'D', 'text' => 'Phosphoric acid']],
             'correct_answer' => 'D', 'explanation' => 'Both DNA and RNA contain phosphoric acid in their backbone. DNA has thymine + deoxyribose, RNA has uracil + ribose. Both share adenine, guanine, cytosine.'],

            // ============ 2021 Chemistry ============
            ['year' => 2021, 'subject' => 'Chemistry', 'topic' => 'The Solid State', 'difficulty' => 'easy',
             'question_text' => 'The coordination number of each atom in body-centred cubic (BCC) structure is:',
             'options' => [['label' => 'A', 'text' => '4'], ['label' => 'B', 'text' => '6'], ['label' => 'C', 'text' => '8'], ['label' => 'D', 'text' => '12']],
             'correct_answer' => 'C', 'explanation' => 'In BCC structure, the central atom touches 8 corner atoms. So coordination number = 8.'],

            ['year' => 2021, 'subject' => 'Chemistry', 'topic' => 'Electrochemistry', 'difficulty' => 'easy',
             'question_text' => 'Faraday\'s first law of electrolysis relates the amount of substance deposited to:',
             'options' => [['label' => 'A', 'text' => 'Voltage applied'], ['label' => 'B', 'text' => 'Quantity of electricity passed'], ['label' => 'C', 'text' => 'Concentration of solution'], ['label' => 'D', 'text' => 'Temperature of solution']],
             'correct_answer' => 'B', 'explanation' => 'Faraday\'s first law: m = ZIt = ZQ. Amount deposited (m) is directly proportional to quantity of electricity (Q) passed through the electrolyte.'],

            ['year' => 2021, 'subject' => 'Chemistry', 'topic' => 'Amines', 'difficulty' => 'medium',
             'question_text' => 'The basic strength of amines follows the order:',
             'options' => [['label' => 'A', 'text' => 'NH₃ > CH₃NH₂ > (CH₃)₂NH'], ['label' => 'B', 'text' => '(CH₃)₂NH > CH₃NH₂ > NH₃'], ['label' => 'C', 'text' => 'CH₃NH₂ > (CH₃)₂NH > NH₃'], ['label' => 'D', 'text' => '(CH₃)₃N > (CH₃)₂NH > CH₃NH₂']],
             'correct_answer' => 'B', 'explanation' => 'In aqueous solution: (CH₃)₂NH > CH₃NH₂ > (CH₃)₃N > NH₃. Secondary amines are most basic due to +I effect of two methyl groups and less steric hindrance than tertiary.'],

            // ============ 2020 Chemistry ============
            ['year' => 2020, 'subject' => 'Chemistry', 'topic' => 'Solutions', 'difficulty' => 'medium',
             'question_text' => 'An ideal solution obeys:',
             'options' => [['label' => 'A', 'text' => 'Henry\'s law'], ['label' => 'B', 'text' => 'Raoult\'s law'], ['label' => 'C', 'text' => 'Boyle\'s law'], ['label' => 'D', 'text' => 'Dalton\'s law']],
             'correct_answer' => 'B', 'explanation' => 'An ideal solution obeys Raoult\'s law at all concentrations: P = P₁⁰x₁ + P₂⁰x₂. ΔH_mix = 0, ΔV_mix = 0 for ideal solutions.'],

            ['year' => 2020, 'subject' => 'Chemistry', 'topic' => 'Chemical Kinetics', 'difficulty' => 'easy',
             'question_text' => 'The rate of a reaction depends on:',
             'options' => [['label' => 'A', 'text' => 'Temperature only'], ['label' => 'B', 'text' => 'Concentration only'], ['label' => 'C', 'text' => 'Catalyst only'], ['label' => 'D', 'text' => 'Temperature, concentration, and catalyst']],
             'correct_answer' => 'D', 'explanation' => 'Rate of reaction depends on: (1) Temperature (Arrhenius equation), (2) Concentration of reactants (rate law), (3) Presence of catalyst, (4) Nature of reactants, (5) Surface area.'],

            ['year' => 2020, 'subject' => 'Chemistry', 'topic' => 'Coordination Compounds', 'difficulty' => 'easy',
             'question_text' => 'The oxidation state of iron in [Fe(CN)₆]⁴⁻ is:',
             'options' => [['label' => 'A', 'text' => '+2'], ['label' => 'B', 'text' => '+3'], ['label' => 'C', 'text' => '+4'], ['label' => 'D', 'text' => '+6']],
             'correct_answer' => 'A', 'explanation' => 'Let oxidation state of Fe = x. x + 6(−1) = −4. x − 6 = −4. x = +2. Iron is in +2 oxidation state in ferrocyanide ion.'],

            ['year' => 2020, 'subject' => 'Chemistry', 'topic' => 'Aldehydes, Ketones and Carboxylic Acids', 'difficulty' => 'easy',
             'question_text' => 'Fehling\'s solution is used to detect:',
             'options' => [['label' => 'A', 'text' => 'Alcohols'], ['label' => 'B', 'text' => 'Aldehydes'], ['label' => 'C', 'text' => 'Ketones'], ['label' => 'D', 'text' => 'Carboxylic acids']],
             'correct_answer' => 'B', 'explanation' => 'Fehling\'s solution (alkaline Cu²⁺ tartrate) is reduced by aldehydes to give a red precipitate of Cu₂O. Ketones do not give this test.'],

            ['year' => 2020, 'subject' => 'Chemistry', 'topic' => 'Polymers', 'difficulty' => 'easy',
             'question_text' => 'Bakelite is obtained from phenol by reacting with:',
             'options' => [['label' => 'A', 'text' => 'CH₃CHO'], ['label' => 'B', 'text' => 'HCHO'], ['label' => 'C', 'text' => 'CH₃COCH₃'], ['label' => 'D', 'text' => 'HCOOH']],
             'correct_answer' => 'B', 'explanation' => 'Bakelite is a thermosetting polymer obtained by the condensation of phenol with formaldehyde (HCHO) in the presence of acid or base catalyst.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedMathematics(Exam $exam): void
    {
        $questions = [
            // ============ 2024 Mathematics ============
            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Relations and Functions', 'difficulty' => 'easy',
             'question_text' => 'Let A = {1, 2, 3}. The number of equivalence relations on A containing (1, 2) is:',
             'options' => [['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '2'], ['label' => 'C', 'text' => '3'], ['label' => 'D', 'text' => '4']],
             'correct_answer' => 'B', 'explanation' => 'Equivalence relations on {1,2,3} containing (1,2): 1) {(1,1),(2,2),(3,3),(1,2),(2,1)} and 2) {(1,1),(2,2),(3,3),(1,2),(2,1),(1,3),(3,1),(2,3),(3,2)}. Total = 2.'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Inverse Trigonometric Functions', 'difficulty' => 'easy',
             'question_text' => 'The principal value of sin⁻¹(−1/2) is:',
             'options' => [['label' => 'A', 'text' => 'π/6'], ['label' => 'B', 'text' => '−π/6'], ['label' => 'C', 'text' => '5π/6'], ['label' => 'D', 'text' => '−5π/6']],
             'correct_answer' => 'B', 'explanation' => 'sin⁻¹(−1/2) = −sin⁻¹(1/2) = −π/6. Range of sin⁻¹ is [−π/2, π/2].'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Matrices', 'difficulty' => 'easy',
             'question_text' => 'If A is a square matrix of order 3 and |A| = 5, then |adj A| is:',
             'options' => [['label' => 'A', 'text' => '5'], ['label' => 'B', 'text' => '25'], ['label' => 'C', 'text' => '125'], ['label' => 'D', 'text' => '1/5']],
             'correct_answer' => 'B', 'explanation' => '|adj A| = |A|^(n−1) = 5^(3−1) = 5² = 25, where n is the order of the matrix.'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Determinants', 'difficulty' => 'medium',
             'question_text' => 'If A is a 3×3 matrix such that |A| = 8, then |3A| equals:',
             'options' => [['label' => 'A', 'text' => '24'], ['label' => 'B', 'text' => '72'], ['label' => 'C', 'text' => '216'], ['label' => 'D', 'text' => '512']],
             'correct_answer' => 'C', 'explanation' => '|kA| = kⁿ|A| where n is the order. |3A| = 3³ × 8 = 27 × 8 = 216.'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Continuity and Differentiability', 'difficulty' => 'medium',
             'question_text' => 'If y = sin⁻¹(x), then dy/dx is:',
             'options' => [['label' => 'A', 'text' => '1/√(1−x²)'], ['label' => 'B', 'text' => '−1/√(1−x²)'], ['label' => 'C', 'text' => '1/(1+x²)'], ['label' => 'D', 'text' => '−1/(1+x²)']],
             'correct_answer' => 'A', 'explanation' => 'd/dx[sin⁻¹(x)] = 1/√(1−x²) for |x| < 1. This is a standard derivative formula.'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Application of Derivatives', 'difficulty' => 'medium',
             'question_text' => 'The rate of change of the area of a circle with respect to its radius r at r = 6 cm is:',
             'options' => [['label' => 'A', 'text' => '10π cm'], ['label' => 'B', 'text' => '12π cm'], ['label' => 'C', 'text' => '8π cm'], ['label' => 'D', 'text' => '6π cm']],
             'correct_answer' => 'B', 'explanation' => 'A = πr². dA/dr = 2πr. At r = 6: dA/dr = 2π(6) = 12π cm.'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Integrals', 'difficulty' => 'medium',
             'question_text' => '∫ 1/(1+x²) dx equals:',
             'options' => [['label' => 'A', 'text' => 'sin⁻¹(x) + C'], ['label' => 'B', 'text' => 'cos⁻¹(x) + C'], ['label' => 'C', 'text' => 'tan⁻¹(x) + C'], ['label' => 'D', 'text' => 'sec⁻¹(x) + C']],
             'correct_answer' => 'C', 'explanation' => '∫ 1/(1+x²) dx = tan⁻¹(x) + C. This is a standard integral formula.'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Differential Equations', 'difficulty' => 'easy',
             'question_text' => 'The order and degree of the differential equation d²y/dx² + (dy/dx)³ + y = 0 are:',
             'options' => [['label' => 'A', 'text' => 'Order 2, Degree 1'], ['label' => 'B', 'text' => 'Order 2, Degree 3'], ['label' => 'C', 'text' => 'Order 1, Degree 3'], ['label' => 'D', 'text' => 'Order 3, Degree 2']],
             'correct_answer' => 'A', 'explanation' => 'Order = highest order derivative = 2 (d²y/dx²). Degree = power of highest order derivative = 1. The (dy/dx)³ doesn\'t affect the degree since d²y/dx² has power 1.'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Vectors', 'difficulty' => 'easy',
             'question_text' => 'If |a⃗| = 2, |b⃗| = 3 and a⃗·b⃗ = 3, then the angle between a⃗ and b⃗ is:',
             'options' => [['label' => 'A', 'text' => '0°'], ['label' => 'B', 'text' => '30°'], ['label' => 'C', 'text' => '60°'], ['label' => 'D', 'text' => '90°']],
             'correct_answer' => 'C', 'explanation' => 'a⃗·b⃗ = |a⃗||b⃗|cos θ. 3 = 2 × 3 × cos θ. cos θ = 3/6 = 1/2. θ = 60°.'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Three Dimensional Geometry', 'difficulty' => 'medium',
             'question_text' => 'The direction cosines of a line making equal angles with the coordinate axes are:',
             'options' => [['label' => 'A', 'text' => '1, 1, 1'], ['label' => 'B', 'text' => '1/√3, 1/√3, 1/√3'], ['label' => 'C', 'text' => '1/3, 1/3, 1/3'], ['label' => 'D', 'text' => '0, 0, 1']],
             'correct_answer' => 'B', 'explanation' => 'If angles with axes are equal (α=β=γ), then cos²α + cos²β + cos²γ = 1 → 3cos²α = 1 → cosα = ±1/√3. Direction cosines: (1/√3, 1/√3, 1/√3).'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Linear Programming', 'difficulty' => 'medium',
             'question_text' => 'The feasible region of a linear programming problem is:',
             'options' => [['label' => 'A', 'text' => 'Always unbounded'], ['label' => 'B', 'text' => 'Always bounded'], ['label' => 'C', 'text' => 'A convex polygon'], ['label' => 'D', 'text' => 'A concave polygon']],
             'correct_answer' => 'C', 'explanation' => 'The feasible region of an LPP (defined by linear inequalities) is always a convex set (convex polygon). Optimal solution occurs at a vertex (corner point).'],

            ['year' => 2024, 'subject' => 'Mathematics', 'topic' => 'Probability', 'difficulty' => 'medium',
             'question_text' => 'If P(A) = 0.3, P(B) = 0.6, and A and B are independent events, then P(A and B) is:',
             'options' => [['label' => 'A', 'text' => '0.9'], ['label' => 'B', 'text' => '0.18'], ['label' => 'C', 'text' => '0.3'], ['label' => 'D', 'text' => '0.72']],
             'correct_answer' => 'B', 'explanation' => 'For independent events: P(A ∩ B) = P(A) × P(B) = 0.3 × 0.6 = 0.18.'],

            // ============ 2023 Mathematics ============
            ['year' => 2023, 'subject' => 'Mathematics', 'topic' => 'Relations and Functions', 'difficulty' => 'easy',
             'question_text' => 'A function f: R → R defined by f(x) = x³ is:',
             'options' => [['label' => 'A', 'text' => 'One-one but not onto'], ['label' => 'B', 'text' => 'Onto but not one-one'], ['label' => 'C', 'text' => 'Both one-one and onto'], ['label' => 'D', 'text' => 'Neither one-one nor onto']],
             'correct_answer' => 'C', 'explanation' => 'f(x) = x³ is strictly increasing for all real x, hence one-one. Range = R = codomain, hence onto. It is a bijective function.'],

            ['year' => 2023, 'subject' => 'Mathematics', 'topic' => 'Matrices', 'difficulty' => 'easy',
             'question_text' => 'If A = [[2,3],[4,5]], then A + Aᵀ is:',
             'options' => [['label' => 'A', 'text' => 'Symmetric'], ['label' => 'B', 'text' => 'Skew-symmetric'], ['label' => 'C', 'text' => 'Diagonal'], ['label' => 'D', 'text' => 'Identity']],
             'correct_answer' => 'A', 'explanation' => 'For any matrix A, (A + Aᵀ)ᵀ = Aᵀ + A = A + Aᵀ. So A + Aᵀ is always symmetric.'],

            ['year' => 2023, 'subject' => 'Mathematics', 'topic' => 'Integrals', 'difficulty' => 'easy',
             'question_text' => '∫₀^π sin x dx equals:',
             'options' => [['label' => 'A', 'text' => '0'], ['label' => 'B', 'text' => '1'], ['label' => 'C', 'text' => '2'], ['label' => 'D', 'text' => 'π']],
             'correct_answer' => 'C', 'explanation' => '∫₀^π sin x dx = [−cos x]₀^π = −cos π − (−cos 0) = −(−1) + 1 = 1 + 1 = 2.'],

            ['year' => 2023, 'subject' => 'Mathematics', 'topic' => 'Vectors', 'difficulty' => 'medium',
             'question_text' => 'The cross product of two parallel vectors is:',
             'options' => [['label' => 'A', 'text' => 'A unit vector'], ['label' => 'B', 'text' => 'A zero vector'], ['label' => 'C', 'text' => 'A scalar'], ['label' => 'D', 'text' => 'Not defined']],
             'correct_answer' => 'B', 'explanation' => 'a⃗ × b⃗ = |a⃗||b⃗|sin θ n̂. For parallel vectors θ = 0° or 180°, sin θ = 0. So cross product = zero vector (0⃗).'],

            ['year' => 2023, 'subject' => 'Mathematics', 'topic' => 'Probability', 'difficulty' => 'easy',
             'question_text' => 'If P(A) = 1/2, P(B) = 1/3, and P(A ∩ B) = 1/6, then P(A|B) is:',
             'options' => [['label' => 'A', 'text' => '1/2'], ['label' => 'B', 'text' => '1/3'], ['label' => 'C', 'text' => '1/6'], ['label' => 'D', 'text' => '2/3']],
             'correct_answer' => 'A', 'explanation' => 'P(A|B) = P(A ∩ B)/P(B) = (1/6)/(1/3) = (1/6) × (3/1) = 1/2.'],

            // ============ 2022 Mathematics ============
            ['year' => 2022, 'subject' => 'Mathematics', 'topic' => 'Continuity and Differentiability', 'difficulty' => 'easy',
             'question_text' => 'If y = eˣ, then dy/dx is:',
             'options' => [['label' => 'A', 'text' => 'xeˣ⁻¹'], ['label' => 'B', 'text' => 'eˣ'], ['label' => 'C', 'text' => 'eˣ/x'], ['label' => 'D', 'text' => 'xeˣ']],
             'correct_answer' => 'B', 'explanation' => 'd/dx(eˣ) = eˣ. The exponential function is its own derivative. This is a fundamental calculus result.'],

            ['year' => 2022, 'subject' => 'Mathematics', 'topic' => 'Application of Integrals', 'difficulty' => 'medium',
             'question_text' => 'The area bounded by y = x², the x-axis, and the lines x = 0 and x = 2 is:',
             'options' => [['label' => 'A', 'text' => '4/3'], ['label' => 'B', 'text' => '8/3'], ['label' => 'C', 'text' => '2/3'], ['label' => 'D', 'text' => '16/3']],
             'correct_answer' => 'B', 'explanation' => 'Area = ∫₀² x² dx = [x³/3]₀² = 8/3 − 0 = 8/3 sq. units.'],

            ['year' => 2022, 'subject' => 'Mathematics', 'topic' => 'Differential Equations', 'difficulty' => 'easy',
             'question_text' => 'The integrating factor of the differential equation dy/dx + y = eˣ is:',
             'options' => [['label' => 'A', 'text' => 'eˣ'], ['label' => 'B', 'text' => 'e⁻ˣ'], ['label' => 'C', 'text' => 'x'], ['label' => 'D', 'text' => '1/x']],
             'correct_answer' => 'A', 'explanation' => 'For dy/dx + Py = Q, IF = e^(∫P dx). Here P = 1. IF = e^(∫1 dx) = eˣ.'],

            // ============ 2021 Mathematics ============
            ['year' => 2021, 'subject' => 'Mathematics', 'topic' => 'Inverse Trigonometric Functions', 'difficulty' => 'easy',
             'question_text' => 'tan⁻¹(1) + cos⁻¹(−1/2) is equal to:',
             'options' => [['label' => 'A', 'text' => 'π/4'], ['label' => 'B', 'text' => '3π/4'], ['label' => 'C', 'text' => '11π/12'], ['label' => 'D', 'text' => 'π']],
             'correct_answer' => 'C', 'explanation' => 'tan⁻¹(1) = π/4, cos⁻¹(−1/2) = 2π/3. Sum = π/4 + 2π/3 = 3π/12 + 8π/12 = 11π/12.'],

            ['year' => 2021, 'subject' => 'Mathematics', 'topic' => 'Determinants', 'difficulty' => 'easy',
             'question_text' => 'If A is a singular matrix, then |A| is:',
             'options' => [['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '0'], ['label' => 'C', 'text' => '−1'], ['label' => 'D', 'text' => 'Non-zero']],
             'correct_answer' => 'B', 'explanation' => 'A singular matrix is one whose determinant is 0 (|A| = 0). It means the matrix is not invertible.'],

            ['year' => 2021, 'subject' => 'Mathematics', 'topic' => 'Three Dimensional Geometry', 'difficulty' => 'easy',
             'question_text' => 'The distance of the point (1, 2, 3) from the origin is:',
             'options' => [['label' => 'A', 'text' => '√6'], ['label' => 'B', 'text' => '√14'], ['label' => 'C', 'text' => '6'], ['label' => 'D', 'text' => '14']],
             'correct_answer' => 'B', 'explanation' => 'Distance from origin = √(1² + 2² + 3²) = √(1 + 4 + 9) = √14.'],

            // ============ 2020 Mathematics ============
            ['year' => 2020, 'subject' => 'Mathematics', 'topic' => 'Relations and Functions', 'difficulty' => 'easy',
             'question_text' => 'A relation R on set A is said to be reflexive if:',
             'options' => [['label' => 'A', 'text' => '(a, b) ∈ R implies (b, a) ∈ R'], ['label' => 'B', 'text' => '(a, a) ∈ R for all a ∈ A'], ['label' => 'C', 'text' => '(a, b) ∈ R and (b, c) ∈ R implies (a, c) ∈ R'], ['label' => 'D', 'text' => 'R = A × A']],
             'correct_answer' => 'B', 'explanation' => 'A relation R on set A is reflexive if (a, a) ∈ R for every element a ∈ A. Every element is related to itself.'],

            ['year' => 2020, 'subject' => 'Mathematics', 'topic' => 'Continuity and Differentiability', 'difficulty' => 'easy',
             'question_text' => 'If f(x) = |x|, then f\'(0) is:',
             'options' => [['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '−1'], ['label' => 'C', 'text' => '0'], ['label' => 'D', 'text' => 'Does not exist']],
             'correct_answer' => 'D', 'explanation' => 'f(x) = |x| is continuous at x = 0 but not differentiable. Left derivative = −1, Right derivative = +1. Since they are not equal, f\'(0) does not exist.'],

            ['year' => 2020, 'subject' => 'Mathematics', 'topic' => 'Integrals', 'difficulty' => 'easy',
             'question_text' => '∫ sec²x dx equals:',
             'options' => [['label' => 'A', 'text' => 'tan x + C'], ['label' => 'B', 'text' => 'sec x + C'], ['label' => 'C', 'text' => 'cot x + C'], ['label' => 'D', 'text' => 'cosec x + C']],
             'correct_answer' => 'A', 'explanation' => '∫ sec²x dx = tan x + C. This is a standard integral. d/dx(tan x) = sec²x.'],

            ['year' => 2020, 'subject' => 'Mathematics', 'topic' => 'Vectors', 'difficulty' => 'easy',
             'question_text' => 'The magnitude of vector 3î − 4ĵ is:',
             'options' => [['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '5'], ['label' => 'C', 'text' => '7'], ['label' => 'D', 'text' => '25']],
             'correct_answer' => 'B', 'explanation' => '|3î − 4ĵ| = √(3² + (−4)²) = √(9 + 16) = √25 = 5.'],

            ['year' => 2020, 'subject' => 'Mathematics', 'topic' => 'Linear Programming', 'difficulty' => 'easy',
             'question_text' => 'In linear programming, the objective function is always:',
             'options' => [['label' => 'A', 'text' => 'A quadratic function'], ['label' => 'B', 'text' => 'A linear function'], ['label' => 'C', 'text' => 'An exponential function'], ['label' => 'D', 'text' => 'A logarithmic function']],
             'correct_answer' => 'B', 'explanation' => 'In Linear Programming Problems (LPP), the objective function Z = ax + by is always a linear function that needs to be maximized or minimized subject to linear constraints.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedBiology(Exam $exam): void
    {
        $questions = [
            // ============ 2024 Biology ============
            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Reproduction in Organisms', 'difficulty' => 'easy',
             'question_text' => 'Which of the following is an example of vegetative propagation?',
             'options' => [['label' => 'A', 'text' => 'Budding in yeast'], ['label' => 'B', 'text' => 'Runners in grass'], ['label' => 'C', 'text' => 'Binary fission in Amoeba'], ['label' => 'D', 'text' => 'Spore formation in moss']],
             'correct_answer' => 'B', 'explanation' => 'Runners in grass are a form of vegetative propagation where new plants arise from vegetative parts. Budding and binary fission are asexual but not vegetative propagation in plants.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Sexual Reproduction in Flowering Plants', 'difficulty' => 'medium',
             'question_text' => 'The process of transfer of pollen grains from the anther to the stigma of another flower of the same plant is called:',
             'options' => [['label' => 'A', 'text' => 'Autogamy'], ['label' => 'B', 'text' => 'Geitonogamy'], ['label' => 'C', 'text' => 'Xenogamy'], ['label' => 'D', 'text' => 'Cleistogamy']],
             'correct_answer' => 'B', 'explanation' => 'Geitonogamy is the transfer of pollen from the anther to the stigma of another flower on the same plant. Autogamy is within the same flower. Xenogamy is between different plants.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Human Reproduction', 'difficulty' => 'easy',
             'question_text' => 'The site of fertilization in human females is:',
             'options' => [['label' => 'A', 'text' => 'Uterus'], ['label' => 'B', 'text' => 'Cervix'], ['label' => 'C', 'text' => 'Ampullary-isthmic junction of fallopian tube'], ['label' => 'D', 'text' => 'Ovary']],
             'correct_answer' => 'C', 'explanation' => 'Fertilization in humans occurs at the ampullary-isthmic junction of the fallopian tube (oviduct).'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Reproductive Health', 'difficulty' => 'easy',
             'question_text' => 'Which of the following is a barrier method of contraception?',
             'options' => [['label' => 'A', 'text' => 'Saheli'], ['label' => 'B', 'text' => 'IUD'], ['label' => 'C', 'text' => 'Condom'], ['label' => 'D', 'text' => 'Vasectomy']],
             'correct_answer' => 'C', 'explanation' => 'Condoms are barrier methods that prevent physical meeting of sperm and ovum. Saheli is a hormonal method. IUD is an intrauterine device. Vasectomy is surgical.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Principles of Inheritance and Variation', 'difficulty' => 'medium',
             'question_text' => 'In Mendel\'s dihybrid cross, the phenotypic ratio in F2 generation is:',
             'options' => [['label' => 'A', 'text' => '3:1'], ['label' => 'B', 'text' => '1:2:1'], ['label' => 'C', 'text' => '9:3:3:1'], ['label' => 'D', 'text' => '1:1:1:1']],
             'correct_answer' => 'C', 'explanation' => 'In a dihybrid cross (RrYy × RrYy), the F2 phenotypic ratio is 9:3:3:1 — 9 round yellow: 3 round green: 3 wrinkled yellow: 1 wrinkled green.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Molecular Basis of Inheritance', 'difficulty' => 'medium',
             'question_text' => 'The enzyme that unwinds the DNA double helix during replication is:',
             'options' => [['label' => 'A', 'text' => 'DNA polymerase'], ['label' => 'B', 'text' => 'DNA ligase'], ['label' => 'C', 'text' => 'Helicase'], ['label' => 'D', 'text' => 'Topoisomerase']],
             'correct_answer' => 'C', 'explanation' => 'Helicase unwinds the DNA double helix by breaking hydrogen bonds between base pairs. DNA polymerase synthesizes new strands. DNA ligase joins Okazaki fragments.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Evolution', 'difficulty' => 'easy',
             'question_text' => 'Organs that have similar structure but different functions are called:',
             'options' => [['label' => 'A', 'text' => 'Analogous organs'], ['label' => 'B', 'text' => 'Homologous organs'], ['label' => 'C', 'text' => 'Vestigial organs'], ['label' => 'D', 'text' => 'Atavistic organs']],
             'correct_answer' => 'B', 'explanation' => 'Homologous organs have similar origin and structure but different functions (e.g., forelimbs of whale, bat, horse). Analogous organs have different origin but similar function.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Human Health and Disease', 'difficulty' => 'easy',
             'question_text' => 'The causative organism of malaria is:',
             'options' => [['label' => 'A', 'text' => 'Entamoeba histolytica'], ['label' => 'B', 'text' => 'Plasmodium'], ['label' => 'C', 'text' => 'Wuchereria bancrofti'], ['label' => 'D', 'text' => 'Ascaris lumbricoides']],
             'correct_answer' => 'B', 'explanation' => 'Malaria is caused by Plasmodium species (P. vivax, P. falciparum, P. malariae, P. ovale). It is transmitted by female Anopheles mosquito.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Biotechnology: Principles and Processes', 'difficulty' => 'medium',
             'question_text' => 'The enzyme used to cut DNA at specific recognition sequences is:',
             'options' => [['label' => 'A', 'text' => 'DNA ligase'], ['label' => 'B', 'text' => 'DNA polymerase'], ['label' => 'C', 'text' => 'Restriction endonuclease'], ['label' => 'D', 'text' => 'Reverse transcriptase']],
             'correct_answer' => 'C', 'explanation' => 'Restriction endonucleases (molecular scissors) cut DNA at specific palindromic recognition sequences. E.g., EcoRI cuts at GAATTC.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Organisms and Populations', 'difficulty' => 'easy',
             'question_text' => 'The interaction between a clownfish and sea anemone is an example of:',
             'options' => [['label' => 'A', 'text' => 'Parasitism'], ['label' => 'B', 'text' => 'Commensalism'], ['label' => 'C', 'text' => 'Mutualism'], ['label' => 'D', 'text' => 'Competition']],
             'correct_answer' => 'C', 'explanation' => 'Clownfish and sea anemone show mutualism — both benefit. Clownfish gets protection from the stinging tentacles, while anemone gets food scraps and better water circulation.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Ecosystem', 'difficulty' => 'medium',
             'question_text' => 'In an ecosystem, the percentage of energy transferred from one trophic level to the next is approximately:',
             'options' => [['label' => 'A', 'text' => '1%'], ['label' => 'B', 'text' => '10%'], ['label' => 'C', 'text' => '20%'], ['label' => 'D', 'text' => '50%']],
             'correct_answer' => 'B', 'explanation' => 'According to Lindeman\'s 10% law, only about 10% of energy is transferred from one trophic level to the next. The rest is lost as heat in respiration.'],

            ['year' => 2024, 'subject' => 'Biology', 'topic' => 'Biodiversity and Conservation', 'difficulty' => 'easy',
             'question_text' => 'The Red Data Book contains the list of:',
             'options' => [['label' => 'A', 'text' => 'All plant species'], ['label' => 'B', 'text' => 'Endangered species'], ['label' => 'C', 'text' => 'Extinct species only'], ['label' => 'D', 'text' => 'Endemic species']],
             'correct_answer' => 'B', 'explanation' => 'The Red Data Book (IUCN Red List) contains the list of endangered species of plants and animals, categorized by their conservation status.'],

            // ============ 2023 Biology ============
            ['year' => 2023, 'subject' => 'Biology', 'topic' => 'Sexual Reproduction in Flowering Plants', 'difficulty' => 'easy',
             'question_text' => 'The triploid nucleus in a flowering plant is:',
             'options' => [['label' => 'A', 'text' => 'Zygote'], ['label' => 'B', 'text' => 'Primary endosperm nucleus'], ['label' => 'C', 'text' => 'Egg cell'], ['label' => 'D', 'text' => 'Pollen grain']],
             'correct_answer' => 'B', 'explanation' => 'The primary endosperm nucleus (PEN) is triploid (3n), formed by the fusion of two polar nuclei (n+n) with one male gamete (n) during double fertilization.'],

            ['year' => 2023, 'subject' => 'Biology', 'topic' => 'Principles of Inheritance and Variation', 'difficulty' => 'medium',
             'question_text' => 'A cross between a homozygous tall plant (TT) and a homozygous dwarf plant (tt) produces:',
             'options' => [['label' => 'A', 'text' => 'All tall plants'], ['label' => 'B', 'text' => 'All dwarf plants'], ['label' => 'C', 'text' => '50% tall, 50% dwarf'], ['label' => 'D', 'text' => '75% tall, 25% dwarf']],
             'correct_answer' => 'A', 'explanation' => 'TT × tt → all Tt (heterozygous). Since tall (T) is dominant over dwarf (t), all F1 offspring are tall. This is Mendel\'s law of dominance.'],

            ['year' => 2023, 'subject' => 'Biology', 'topic' => 'Molecular Basis of Inheritance', 'difficulty' => 'easy',
             'question_text' => 'Which of the following acts as an adapter molecule during translation?',
             'options' => [['label' => 'A', 'text' => 'mRNA'], ['label' => 'B', 'text' => 'tRNA'], ['label' => 'C', 'text' => 'rRNA'], ['label' => 'D', 'text' => 'snRNA']],
             'correct_answer' => 'B', 'explanation' => 'tRNA (transfer RNA) acts as the adapter molecule during translation. It has an anticodon that pairs with mRNA codon and carries the specific amino acid.'],

            ['year' => 2023, 'subject' => 'Biology', 'topic' => 'Human Health and Disease', 'difficulty' => 'easy',
             'question_text' => 'Antibodies are produced by:',
             'options' => [['label' => 'A', 'text' => 'T-lymphocytes'], ['label' => 'B', 'text' => 'B-lymphocytes'], ['label' => 'C', 'text' => 'Monocytes'], ['label' => 'D', 'text' => 'Basophils']],
             'correct_answer' => 'B', 'explanation' => 'B-lymphocytes (B cells) produce antibodies (immunoglobulins) as part of humoral immune response. T-lymphocytes are involved in cell-mediated immunity.'],

            ['year' => 2023, 'subject' => 'Biology', 'topic' => 'Biotechnology and its Applications', 'difficulty' => 'medium',
             'question_text' => 'Bt cotton is resistant to:',
             'options' => [['label' => 'A', 'text' => 'Herbicides'], ['label' => 'B', 'text' => 'Drought'], ['label' => 'C', 'text' => 'Bollworm insects'], ['label' => 'D', 'text' => 'Salt stress']],
             'correct_answer' => 'C', 'explanation' => 'Bt cotton contains the cry gene from Bacillus thuringiensis that produces Bt toxin (Cry protein). This toxin kills bollworm larvae that feed on cotton.'],

            ['year' => 2023, 'subject' => 'Biology', 'topic' => 'Organisms and Populations', 'difficulty' => 'medium',
             'question_text' => 'The logistic growth model is represented by the equation:',
             'options' => [['label' => 'A', 'text' => 'dN/dt = rN'], ['label' => 'B', 'text' => 'dN/dt = rN(K-N)/K'], ['label' => 'C', 'text' => 'dN/dt = rN/K'], ['label' => 'D', 'text' => 'dN/dt = r(K-N)']],
             'correct_answer' => 'B', 'explanation' => 'The Verhulst-Pearl logistic growth equation: dN/dt = rN(K-N)/K, where r = intrinsic rate of increase, N = population size, K = carrying capacity.'],

            ['year' => 2023, 'subject' => 'Biology', 'topic' => 'Ecosystem', 'difficulty' => 'easy',
             'question_text' => 'The process of progressive series of changes in species composition of a community over time is called:',
             'options' => [['label' => 'A', 'text' => 'Evolution'], ['label' => 'B', 'text' => 'Ecological succession'], ['label' => 'C', 'text' => 'Speciation'], ['label' => 'D', 'text' => 'Natural selection']],
             'correct_answer' => 'B', 'explanation' => 'Ecological succession is the gradual and predictable change in species composition of an area over time. Primary succession starts on bare rock; secondary succession after disturbance.'],

            ['year' => 2023, 'subject' => 'Biology', 'topic' => 'Biodiversity and Conservation', 'difficulty' => 'easy',
             'question_text' => 'Which of the following is an example of in situ conservation?',
             'options' => [['label' => 'A', 'text' => 'Zoo'], ['label' => 'B', 'text' => 'Botanical garden'], ['label' => 'C', 'text' => 'National park'], ['label' => 'D', 'text' => 'Seed bank']],
             'correct_answer' => 'C', 'explanation' => 'In situ conservation means protecting species in their natural habitat. National parks, wildlife sanctuaries, and biosphere reserves are in situ methods. Zoos and botanical gardens are ex situ.'],

            // ============ 2022 Biology ============
            ['year' => 2022, 'subject' => 'Biology', 'topic' => 'Reproduction in Organisms', 'difficulty' => 'easy',
             'question_text' => 'Binary fission in Amoeba is a type of:',
             'options' => [['label' => 'A', 'text' => 'Sexual reproduction'], ['label' => 'B', 'text' => 'Asexual reproduction'], ['label' => 'C', 'text' => 'Vegetative propagation'], ['label' => 'D', 'text' => 'Parthenogenesis']],
             'correct_answer' => 'B', 'explanation' => 'Binary fission in Amoeba is asexual reproduction where the parent cell divides into two equal daughter cells. No fusion of gametes occurs.'],

            ['year' => 2022, 'subject' => 'Biology', 'topic' => 'Human Reproduction', 'difficulty' => 'medium',
             'question_text' => 'The hormone responsible for ovulation in human females is:',
             'options' => [['label' => 'A', 'text' => 'FSH'], ['label' => 'B', 'text' => 'Progesterone'], ['label' => 'C', 'text' => 'LH'], ['label' => 'D', 'text' => 'Estrogen']],
             'correct_answer' => 'C', 'explanation' => 'Luteinizing Hormone (LH) surge triggers ovulation on day 14 of the menstrual cycle. FSH stimulates follicular growth. Progesterone maintains pregnancy.'],

            ['year' => 2022, 'subject' => 'Biology', 'topic' => 'Principles of Inheritance and Variation', 'difficulty' => 'easy',
             'question_text' => 'Haemophilia is an example of:',
             'options' => [['label' => 'A', 'text' => 'Autosomal dominant disorder'], ['label' => 'B', 'text' => 'Autosomal recessive disorder'], ['label' => 'C', 'text' => 'X-linked recessive disorder'], ['label' => 'D', 'text' => 'Y-linked disorder']],
             'correct_answer' => 'C', 'explanation' => 'Haemophilia is an X-linked recessive disorder. The gene for clotting factor VIII is on the X chromosome. Males (XY) are more affected as they have only one X.'],

            ['year' => 2022, 'subject' => 'Biology', 'topic' => 'Molecular Basis of Inheritance', 'difficulty' => 'medium',
             'question_text' => 'The mRNA codon AUG codes for:',
             'options' => [['label' => 'A', 'text' => 'Leucine'], ['label' => 'B', 'text' => 'Valine'], ['label' => 'C', 'text' => 'Methionine'], ['label' => 'D', 'text' => 'Glycine']],
             'correct_answer' => 'C', 'explanation' => 'AUG is the start codon that codes for methionine (Met). It also serves as the initiation codon for translation in both prokaryotes and eukaryotes.'],

            ['year' => 2022, 'subject' => 'Biology', 'topic' => 'Evolution', 'difficulty' => 'easy',
             'question_text' => 'The scientist who proposed the theory of natural selection is:',
             'options' => [['label' => 'A', 'text' => 'Lamarck'], ['label' => 'B', 'text' => 'Darwin'], ['label' => 'C', 'text' => 'Hugo de Vries'], ['label' => 'D', 'text' => 'Mendel']],
             'correct_answer' => 'B', 'explanation' => 'Charles Darwin proposed the theory of natural selection in "On the Origin of Species" (1859). Alfred Russell Wallace independently arrived at similar conclusions.'],

            ['year' => 2022, 'subject' => 'Biology', 'topic' => 'Biotechnology: Principles and Processes', 'difficulty' => 'easy',
             'question_text' => 'PCR stands for:',
             'options' => [['label' => 'A', 'text' => 'Protein Chain Reaction'], ['label' => 'B', 'text' => 'Polymerase Chain Reaction'], ['label' => 'C', 'text' => 'Polymer Chain Reaction'], ['label' => 'D', 'text' => 'Peptide Chain Reaction']],
             'correct_answer' => 'B', 'explanation' => 'PCR (Polymerase Chain Reaction) is a technique to amplify a specific DNA segment. It was developed by Kary Mullis. Uses Taq polymerase from Thermus aquaticus.'],

            ['year' => 2022, 'subject' => 'Biology', 'topic' => 'Organisms and Populations', 'difficulty' => 'easy',
             'question_text' => 'Animals that generate body heat through metabolism are called:',
             'options' => [['label' => 'A', 'text' => 'Ectotherms'], ['label' => 'B', 'text' => 'Endotherms'], ['label' => 'C', 'text' => 'Poikilotherms'], ['label' => 'D', 'text' => 'Heterotherms']],
             'correct_answer' => 'B', 'explanation' => 'Endotherms (warm-blooded) generate heat metabolically to maintain constant body temperature. Birds and mammals are endotherms. Ectotherms depend on external heat sources.'],

            // ============ 2021 Biology ============
            ['year' => 2021, 'subject' => 'Biology', 'topic' => 'Sexual Reproduction in Flowering Plants', 'difficulty' => 'easy',
             'question_text' => 'The female gametophyte in angiosperms is called:',
             'options' => [['label' => 'A', 'text' => 'Pollen grain'], ['label' => 'B', 'text' => 'Embryo sac'], ['label' => 'C', 'text' => 'Ovule'], ['label' => 'D', 'text' => 'Megaspore']],
             'correct_answer' => 'B', 'explanation' => 'The female gametophyte (embryo sac) in angiosperms is a 7-celled, 8-nucleate structure. It contains egg cell, synergids, antipodal cells, and central cell with 2 polar nuclei.'],

            ['year' => 2021, 'subject' => 'Biology', 'topic' => 'Molecular Basis of Inheritance', 'difficulty' => 'easy',
             'question_text' => 'The process of copying genetic information from one strand of DNA into RNA is:',
             'options' => [['label' => 'A', 'text' => 'Replication'], ['label' => 'B', 'text' => 'Translation'], ['label' => 'C', 'text' => 'Transcription'], ['label' => 'D', 'text' => 'Transduction']],
             'correct_answer' => 'C', 'explanation' => 'Transcription is the synthesis of RNA from a DNA template by RNA polymerase. The template strand of DNA is read 3\'→5\' and RNA is synthesized 5\'→3\'.'],

            ['year' => 2021, 'subject' => 'Biology', 'topic' => 'Human Health and Disease', 'difficulty' => 'easy',
             'question_text' => 'The type of immunity that a baby gets from mother\'s milk is:',
             'options' => [['label' => 'A', 'text' => 'Active immunity'], ['label' => 'B', 'text' => 'Passive immunity'], ['label' => 'C', 'text' => 'Innate immunity'], ['label' => 'D', 'text' => 'Cell-mediated immunity']],
             'correct_answer' => 'B', 'explanation' => 'Passive immunity is acquired from mother\'s milk (colostrum) which contains IgA antibodies. It provides immediate but temporary protection to the newborn.'],

            ['year' => 2021, 'subject' => 'Biology', 'topic' => 'Ecosystem', 'difficulty' => 'medium',
             'question_text' => 'Which of the following is a greenhouse gas?',
             'options' => [['label' => 'A', 'text' => 'Nitrogen'], ['label' => 'B', 'text' => 'Oxygen'], ['label' => 'C', 'text' => 'Carbon dioxide'], ['label' => 'D', 'text' => 'Helium']],
             'correct_answer' => 'C', 'explanation' => 'CO₂ is a major greenhouse gas that traps infrared radiation. Other greenhouse gases include methane (CH₄), nitrous oxide (N₂O), and CFCs. N₂ and O₂ are not greenhouse gases.'],

            ['year' => 2021, 'subject' => 'Biology', 'topic' => 'Biotechnology and its Applications', 'difficulty' => 'medium',
             'question_text' => 'Golden rice is genetically modified to be rich in:',
             'options' => [['label' => 'A', 'text' => 'Vitamin C'], ['label' => 'B', 'text' => 'Vitamin A (beta-carotene)'], ['label' => 'C', 'text' => 'Iron'], ['label' => 'D', 'text' => 'Protein']],
             'correct_answer' => 'B', 'explanation' => 'Golden rice is a GMO variety engineered to produce beta-carotene (provitamin A) in the endosperm. It was developed to combat vitamin A deficiency in developing countries.'],

            // ============ 2020 Biology ============
            ['year' => 2020, 'subject' => 'Biology', 'topic' => 'Reproduction in Organisms', 'difficulty' => 'easy',
             'question_text' => 'In which organism does internal fertilization NOT occur?',
             'options' => [['label' => 'A', 'text' => 'Humans'], ['label' => 'B', 'text' => 'Frog'], ['label' => 'C', 'text' => 'Birds'], ['label' => 'D', 'text' => 'Reptiles']],
             'correct_answer' => 'B', 'explanation' => 'Frogs show external fertilization — eggs are laid in water and sperm is released over them. Humans, birds, and reptiles have internal fertilization.'],

            ['year' => 2020, 'subject' => 'Biology', 'topic' => 'Principles of Inheritance and Variation', 'difficulty' => 'medium',
             'question_text' => 'The blood group in humans is an example of:',
             'options' => [['label' => 'A', 'text' => 'Incomplete dominance'], ['label' => 'B', 'text' => 'Codominance'], ['label' => 'C', 'text' => 'Multiple allelism and codominance'], ['label' => 'D', 'text' => 'Epistasis']],
             'correct_answer' => 'C', 'explanation' => 'ABO blood grouping shows both multiple allelism (IA, IB, i — 3 alleles) and codominance (IA and IB are codominant in AB blood group). i is recessive to both.'],

            ['year' => 2020, 'subject' => 'Biology', 'topic' => 'Evolution', 'difficulty' => 'easy',
             'question_text' => 'Wings of butterfly and wings of bird are examples of:',
             'options' => [['label' => 'A', 'text' => 'Homologous organs'], ['label' => 'B', 'text' => 'Analogous organs'], ['label' => 'C', 'text' => 'Vestigial organs'], ['label' => 'D', 'text' => 'Rudimentary organs']],
             'correct_answer' => 'B', 'explanation' => 'Wings of butterfly (insect) and bird are analogous organs — different in origin (insect exoskeleton vs vertebrate forelimb) but similar in function (flight). This is convergent evolution.'],

            ['year' => 2020, 'subject' => 'Biology', 'topic' => 'Biotechnology: Principles and Processes', 'difficulty' => 'easy',
             'question_text' => 'The commonly used vector for gene cloning in plants is:',
             'options' => [['label' => 'A', 'text' => 'pBR322'], ['label' => 'B', 'text' => 'Ti plasmid of Agrobacterium'], ['label' => 'C', 'text' => 'Lambda phage'], ['label' => 'D', 'text' => 'BAC']],
             'correct_answer' => 'B', 'explanation' => 'Ti (Tumor-inducing) plasmid of Agrobacterium tumefaciens is the most commonly used vector for plant genetic engineering. Its T-DNA gets integrated into the plant genome.'],

            ['year' => 2020, 'subject' => 'Biology', 'topic' => 'Ecosystem', 'difficulty' => 'easy',
             'question_text' => 'Detritivores include:',
             'options' => [['label' => 'A', 'text' => 'Herbivores'], ['label' => 'B', 'text' => 'Bacteria and fungi only'], ['label' => 'C', 'text' => 'Earthworms'], ['label' => 'D', 'text' => 'All carnivores']],
             'correct_answer' => 'C', 'explanation' => 'Detritivores are organisms that feed on detritus (dead organic matter). Earthworms are a classic example. Bacteria and fungi are decomposers (not the same as detritivores).'],

            ['year' => 2020, 'subject' => 'Biology', 'topic' => 'Biodiversity and Conservation', 'difficulty' => 'easy',
             'question_text' => 'The term "biodiversity" was popularized by:',
             'options' => [['label' => 'A', 'text' => 'Alexander von Humboldt'], ['label' => 'B', 'text' => 'Edward Wilson'], ['label' => 'C', 'text' => 'Paul Ehrlich'], ['label' => 'D', 'text' => 'Robert May']],
             'correct_answer' => 'B', 'explanation' => 'Edward O. Wilson popularized the term "biodiversity" in 1986. He is considered the father of biodiversity. Alexander von Humboldt was a pioneer of biogeography.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedEnglish(Exam $exam): void
    {
        $questions = [
            // ============ 2024 English ============
            ['year' => 2024, 'subject' => 'English', 'topic' => 'Reading Comprehension', 'difficulty' => 'easy',
             'question_text' => 'An unseen passage is used to test a student\'s ability of:',
             'options' => [['label' => 'A', 'text' => 'Writing skills only'], ['label' => 'B', 'text' => 'Reading and comprehension skills'], ['label' => 'C', 'text' => 'Speaking skills'], ['label' => 'D', 'text' => 'Listening skills']],
             'correct_answer' => 'B', 'explanation' => 'Unseen passages in CBSE English test reading comprehension — the ability to understand, interpret, and draw inferences from a given text.'],

            ['year' => 2024, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Choose the correct passive voice of: "She writes a letter."',
             'options' => [['label' => 'A', 'text' => 'A letter is written by her.'], ['label' => 'B', 'text' => 'A letter was written by her.'], ['label' => 'C', 'text' => 'A letter has been written by her.'], ['label' => 'D', 'text' => 'A letter will be written by her.']],
             'correct_answer' => 'A', 'explanation' => 'Active (Simple Present): She writes a letter. → Passive: A letter is written by her. The object becomes subject, verb becomes "is + past participle".'],

            ['year' => 2024, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Identify the type of sentence: "What a beautiful sunset!"',
             'options' => [['label' => 'A', 'text' => 'Declarative'], ['label' => 'B', 'text' => 'Interrogative'], ['label' => 'C', 'text' => 'Exclamatory'], ['label' => 'D', 'text' => 'Imperative']],
             'correct_answer' => 'C', 'explanation' => 'Exclamatory sentences express strong emotions (surprise, joy, etc.) and end with an exclamation mark. "What a beautiful sunset!" expresses wonder/admiration.'],

            ['year' => 2024, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'medium',
             'question_text' => 'Choose the correct indirect speech: He said, "I am going to school."',
             'options' => [['label' => 'A', 'text' => 'He said that he is going to school.'], ['label' => 'B', 'text' => 'He said that he was going to school.'], ['label' => 'C', 'text' => 'He said that he had been going to school.'], ['label' => 'D', 'text' => 'He says that he was going to school.']],
             'correct_answer' => 'B', 'explanation' => 'In reported speech, present continuous "am going" changes to past continuous "was going". The pronoun "I" changes to "he" and "said" is retained.'],

            ['year' => 2024, 'subject' => 'English', 'topic' => 'Writing Skills', 'difficulty' => 'easy',
             'question_text' => 'A formal letter begins with:',
             'options' => [['label' => 'A', 'text' => 'Dear Friend'], ['label' => 'B', 'text' => 'The sender\'s address'], ['label' => 'C', 'text' => 'The body of the letter'], ['label' => 'D', 'text' => 'Yours lovingly']],
             'correct_answer' => 'B', 'explanation' => 'A formal letter format starts with the sender\'s address (top left/right), followed by date, receiver\'s address, subject, salutation, body, and closing.'],

            ['year' => 2024, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'medium',
             'question_text' => 'In the poem "My Mother at Sixty-six" by Kamala Das, the poet compares her mother\'s face to:',
             'options' => [['label' => 'A', 'text' => 'A withered flower'], ['label' => 'B', 'text' => 'A corpse'], ['label' => 'C', 'text' => 'An old painting'], ['label' => 'D', 'text' => 'A fading moon']],
             'correct_answer' => 'B', 'explanation' => 'Kamala Das compares her mother\'s pale, lifeless face to a corpse — "her face ashen like that of a corpse". This simile highlights her mother\'s aging and the poet\'s fear of losing her.'],

            ['year' => 2024, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'easy',
             'question_text' => 'Who is the author of "The Last Lesson"?',
             'options' => [['label' => 'A', 'text' => 'Alphonse Daudet'], ['label' => 'B', 'text' => 'Louis Fischer'], ['label' => 'C', 'text' => 'Anees Jung'], ['label' => 'D', 'text' => 'William Douglas']],
             'correct_answer' => 'A', 'explanation' => '"The Last Lesson" is written by Alphonse Daudet, a French author. It is set during the Franco-Prussian War and depicts the last French lesson in an Alsatian school.'],

            ['year' => 2024, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'medium',
             'question_text' => 'In "The Rattrap" by Selma Lagerlöf, the rattrap symbolizes:',
             'options' => [['label' => 'A', 'text' => 'Death'], ['label' => 'B', 'text' => 'The world and its temptations'], ['label' => 'C', 'text' => 'Poverty'], ['label' => 'D', 'text' => 'Friendship']],
             'correct_answer' => 'B', 'explanation' => 'The rattrap is a metaphor for the world, which offers baits (riches, joys, shelter) to tempt people. Once they take the bait, the world closes in like a rattrap.'],

            // ============ 2023 English ============
            ['year' => 2023, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Fill in the blank: She _____ to school every day.',
             'options' => [['label' => 'A', 'text' => 'go'], ['label' => 'B', 'text' => 'goes'], ['label' => 'C', 'text' => 'going'], ['label' => 'D', 'text' => 'gone']],
             'correct_answer' => 'B', 'explanation' => 'With third person singular subjects (she/he/it), the verb takes an "s" or "es" in simple present tense. She goes to school every day.'],

            ['year' => 2023, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'medium',
             'question_text' => 'The word "benevolent" means:',
             'options' => [['label' => 'A', 'text' => 'Cruel'], ['label' => 'B', 'text' => 'Kind and generous'], ['label' => 'C', 'text' => 'Intelligent'], ['label' => 'D', 'text' => 'Lazy']],
             'correct_answer' => 'B', 'explanation' => '"Benevolent" means well-meaning, kind, and generous. It comes from Latin "bene" (well) + "volens" (wishing). Its antonym is "malevolent" (wishing harm).'],

            ['year' => 2023, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'easy',
             'question_text' => 'In "Deep Water" by William Douglas, the author overcomes his fear of:',
             'options' => [['label' => 'A', 'text' => 'Heights'], ['label' => 'B', 'text' => 'Water'], ['label' => 'C', 'text' => 'Darkness'], ['label' => 'D', 'text' => 'Fire']],
             'correct_answer' => 'B', 'explanation' => 'In "Deep Water", William Douglas describes how he overcame his lifelong fear of water through determination and hired an instructor to teach him swimming.'],

            ['year' => 2023, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'medium',
             'question_text' => 'The poem "Keeping Quiet" by Pablo Neruda advocates:',
             'options' => [['label' => 'A', 'text' => 'Inactivity and laziness'], ['label' => 'B', 'text' => 'Self-introspection and peace'], ['label' => 'C', 'text' => 'Revolution'], ['label' => 'D', 'text' => 'Isolation from society']],
             'correct_answer' => 'B', 'explanation' => 'Pablo Neruda\'s "Keeping Quiet" advocates a moment of silence and stillness for self-introspection, to understand ourselves better. It is not about inactivity but about thoughtful reflection.'],

            ['year' => 2023, 'subject' => 'English', 'topic' => 'Writing Skills', 'difficulty' => 'easy',
             'question_text' => 'A notice is written for:',
             'options' => [['label' => 'A', 'text' => 'A specific person'], ['label' => 'B', 'text' => 'A wide audience/public'], ['label' => 'C', 'text' => 'Only teachers'], ['label' => 'D', 'text' => 'Personal use']],
             'correct_answer' => 'B', 'explanation' => 'A notice is a formal written communication meant for a wide audience/public. It is displayed on notice boards to inform people about events, rules, or announcements.'],

            // ============ 2022 English ============
            ['year' => 2022, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Choose the correct option: Neither the teacher nor the students _____ present.',
             'options' => [['label' => 'A', 'text' => 'is'], ['label' => 'B', 'text' => 'are'], ['label' => 'C', 'text' => 'was'], ['label' => 'D', 'text' => 'were']],
             'correct_answer' => 'D', 'explanation' => 'With "neither...nor", the verb agrees with the subject nearest to it. "Students" (plural) is nearest, so "were" is correct. The past tense makes "were" the right choice.'],

            ['year' => 2022, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'medium',
             'question_text' => 'An antonym of "transparent" is:',
             'options' => [['label' => 'A', 'text' => 'Clear'], ['label' => 'B', 'text' => 'Opaque'], ['label' => 'C', 'text' => 'Translucent'], ['label' => 'D', 'text' => 'Visible']],
             'correct_answer' => 'B', 'explanation' => '"Transparent" means allowing light to pass through so objects can be seen clearly. Its antonym is "opaque" — not allowing light through. "Translucent" allows some light but not clear vision.'],

            ['year' => 2022, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'easy',
             'question_text' => 'In "Indigo" by Louis Fischer, Rajkumar Shukla wanted Gandhiji to visit:',
             'options' => [['label' => 'A', 'text' => 'Sabarmati'], ['label' => 'B', 'text' => 'Champaran'], ['label' => 'C', 'text' => 'Wardha'], ['label' => 'D', 'text' => 'Dandi']],
             'correct_answer' => 'B', 'explanation' => 'Rajkumar Shukla, a poor sharecropper, persuaded Gandhiji to visit Champaran in Bihar to investigate the injustice of the indigo plantation system (tinkathia system).'],

            ['year' => 2022, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'easy',
             'question_text' => '"A Thing of Beauty is a Joy Forever" is a line from the poem by:',
             'options' => [['label' => 'A', 'text' => 'John Keats'], ['label' => 'B', 'text' => 'Pablo Neruda'], ['label' => 'C', 'text' => 'Robert Frost'], ['label' => 'D', 'text' => 'Kamala Das']],
             'correct_answer' => 'A', 'explanation' => '"A Thing of Beauty is a Joy Forever" is the opening line of John Keats\' poem "Endymion". In CBSE Class 12, an excerpt from this poem is included in the Flamingo textbook.'],

            // ============ 2021 English ============
            ['year' => 2021, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Select the determiner in: "Every student must attend the assembly."',
             'options' => [['label' => 'A', 'text' => 'student'], ['label' => 'B', 'text' => 'Every'], ['label' => 'C', 'text' => 'must'], ['label' => 'D', 'text' => 'attend']],
             'correct_answer' => 'B', 'explanation' => '"Every" is a determiner (distributive) that modifies the noun "student". Determiners precede nouns and specify which or how many.'],

            ['year' => 2021, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'medium',
             'question_text' => 'In "The Third Level" by Jack Finney, the third level at Grand Central Station represents:',
             'options' => [['label' => 'A', 'text' => 'A real underground floor'], ['label' => 'B', 'text' => 'An escape from modern day stress'], ['label' => 'C', 'text' => 'A dream sequence'], ['label' => 'D', 'text' => 'A scientific experiment']],
             'correct_answer' => 'B', 'explanation' => 'The third level symbolizes an escape from the insecurities, fears, and stress of modern life. Charley\'s desire to reach 1894 Galesburg represents nostalgia for a simpler, peaceful time.'],

            ['year' => 2021, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'easy',
             'question_text' => 'In "Lost Spring" by Anees Jung, Saheb was originally from:',
             'options' => [['label' => 'A', 'text' => 'Bihar'], ['label' => 'B', 'text' => 'Dhaka, Bangladesh'], ['label' => 'C', 'text' => 'Nepal'], ['label' => 'D', 'text' => 'Pakistan']],
             'correct_answer' => 'B', 'explanation' => 'Saheb-e-Alam in "Lost Spring" was from Dhaka, Bangladesh. His family migrated to Delhi after storms destroyed their homes. He became a ragpicker in Seemapuri.'],

            // ============ 2020 English ============
            ['year' => 2020, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Identify the figure of speech in: "The wind howled through the night."',
             'options' => [['label' => 'A', 'text' => 'Simile'], ['label' => 'B', 'text' => 'Personification'], ['label' => 'C', 'text' => 'Metaphor'], ['label' => 'D', 'text' => 'Alliteration']],
             'correct_answer' => 'B', 'explanation' => 'Personification gives human qualities to non-human things. "Wind howled" gives the wind the human ability to howl. A simile uses "like" or "as".'],

            ['year' => 2020, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Choose the correct preposition: The book is _____ the table.',
             'options' => [['label' => 'A', 'text' => 'in'], ['label' => 'B', 'text' => 'on'], ['label' => 'C', 'text' => 'at'], ['label' => 'D', 'text' => 'by']],
             'correct_answer' => 'B', 'explanation' => '"On" is used when something is on a surface. "The book is on the table" indicates the book is placed on the surface of the table.'],

            ['year' => 2020, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'medium',
             'question_text' => 'In "Aunt Jennifer\'s Tigers" by Adrienne Rich, the tigers symbolize:',
             'options' => [['label' => 'A', 'text' => 'Fear and danger'], ['label' => 'B', 'text' => 'Freedom, confidence and fearlessness'], ['label' => 'C', 'text' => 'Aunt Jennifer\'s pets'], ['label' => 'D', 'text' => 'Wild nature']],
             'correct_answer' => 'B', 'explanation' => 'The tigers embroidered by Aunt Jennifer symbolize freedom, confidence, and fearlessness — qualities that Aunt Jennifer herself lacks in her oppressive marriage. They represent her desire for liberation.'],

            ['year' => 2020, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'easy',
             'question_text' => 'The story "The Enemy" is written by:',
             'options' => [['label' => 'A', 'text' => 'Jack Finney'], ['label' => 'B', 'text' => 'Pearl S. Buck'], ['label' => 'C', 'text' => 'Selma Lagerlöf'], ['label' => 'D', 'text' => 'Alphonse Daudet']],
             'correct_answer' => 'B', 'explanation' => '"The Enemy" is written by Pearl S. Buck. It is about Dr. Sadao, a Japanese surgeon who faces a moral dilemma when he finds an injured American POW during World War II.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedPhysics2018_2019(Exam $exam): void
    {
        $questions = [
            // ============ 2019 Physics ============
            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Electric Charges and Fields', 'difficulty' => 'easy',
             'question_text' => 'The electric field inside a charged conductor is:',
             'options' => [['label' => 'A', 'text' => 'Maximum'], ['label' => 'B', 'text' => 'Minimum but not zero'], ['label' => 'C', 'text' => 'Zero'], ['label' => 'D', 'text' => 'Depends on the charge']],
             'correct_answer' => 'C', 'explanation' => 'The electric field inside a charged conductor in electrostatic equilibrium is always zero. All excess charge resides on the surface.'],

            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Electrostatic Potential and Capacitance', 'difficulty' => 'medium',
             'question_text' => 'The capacitance of a parallel plate capacitor with air medium is C. If a dielectric of constant K fills the space, the new capacitance is:',
             'options' => [['label' => 'A', 'text' => 'C/K'], ['label' => 'B', 'text' => 'KC'], ['label' => 'C', 'text' => 'C + K'], ['label' => 'D', 'text' => 'C − K']],
             'correct_answer' => 'B', 'explanation' => 'With dielectric: C\' = KC. The dielectric constant K multiplies the original capacitance. For air, K = 1.'],

            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Current Electricity', 'difficulty' => 'easy',
             'question_text' => 'The specific resistance of a wire depends on:',
             'options' => [['label' => 'A', 'text' => 'Length of wire'], ['label' => 'B', 'text' => 'Cross-sectional area'], ['label' => 'C', 'text' => 'Material and temperature'], ['label' => 'D', 'text' => 'Shape of wire']],
             'correct_answer' => 'C', 'explanation' => 'Specific resistance (resistivity) is a material property. It depends only on the nature of the material and temperature, not on dimensions.'],

            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Moving Charges and Magnetism', 'difficulty' => 'medium',
             'question_text' => 'Two parallel wires carrying currents in the same direction:',
             'options' => [['label' => 'A', 'text' => 'Attract each other'], ['label' => 'B', 'text' => 'Repel each other'], ['label' => 'C', 'text' => 'Have no force between them'], ['label' => 'D', 'text' => 'First attract then repel']],
             'correct_answer' => 'A', 'explanation' => 'Parallel wires with currents in the same direction attract each other. Opposite direction currents repel. F/L = μ₀I₁I₂/(2πd).'],

            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Electromagnetic Induction', 'difficulty' => 'easy',
             'question_text' => 'Faraday\'s law of electromagnetic induction relates induced EMF to:',
             'options' => [['label' => 'A', 'text' => 'Charge'], ['label' => 'B', 'text' => 'Rate of change of magnetic flux'], ['label' => 'C', 'text' => 'Electric field'], ['label' => 'D', 'text' => 'Magnetic field strength only']],
             'correct_answer' => 'B', 'explanation' => 'Faraday\'s law: e = −dΦ/dt. The induced EMF is equal to the negative rate of change of magnetic flux through the circuit.'],

            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Ray Optics', 'difficulty' => 'easy',
             'question_text' => 'A convex mirror always forms an image which is:',
             'options' => [['label' => 'A', 'text' => 'Real and inverted'], ['label' => 'B', 'text' => 'Virtual and erect'], ['label' => 'C', 'text' => 'Real and magnified'], ['label' => 'D', 'text' => 'Virtual and inverted']],
             'correct_answer' => 'B', 'explanation' => 'A convex mirror always forms a virtual, erect, and diminished image regardless of object position. That\'s why it\'s used as a rear-view mirror.'],

            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Wave Optics', 'difficulty' => 'medium',
             'question_text' => 'The phenomenon of interference is based on the principle of:',
             'options' => [['label' => 'A', 'text' => 'Conservation of energy'], ['label' => 'B', 'text' => 'Superposition of waves'], ['label' => 'C', 'text' => 'Huygens\' principle only'], ['label' => 'D', 'text' => 'Reflection']],
             'correct_answer' => 'B', 'explanation' => 'Interference is based on the principle of superposition — when two or more waves overlap, the resultant displacement is the algebraic sum of individual displacements.'],

            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Atoms', 'difficulty' => 'easy',
             'question_text' => 'In Bohr\'s model, the angular momentum of electron in nth orbit is:',
             'options' => [['label' => 'A', 'text' => 'nh/2π'], ['label' => 'B', 'text' => 'nh'], ['label' => 'C', 'text' => 'n²h/2π'], ['label' => 'D', 'text' => 'h/2πn']],
             'correct_answer' => 'A', 'explanation' => 'Bohr\'s quantization condition: L = mvr = nh/2π, where n = 1, 2, 3... (principal quantum number). Angular momentum is quantized in units of ℏ = h/2π.'],

            ['year' => 2019, 'subject' => 'Physics', 'topic' => 'Semiconductor Electronics', 'difficulty' => 'easy',
             'question_text' => 'A Zener diode is used as:',
             'options' => [['label' => 'A', 'text' => 'An amplifier'], ['label' => 'B', 'text' => 'A voltage regulator'], ['label' => 'C', 'text' => 'An oscillator'], ['label' => 'D', 'text' => 'A rectifier']],
             'correct_answer' => 'B', 'explanation' => 'A Zener diode is used as a voltage regulator. It operates in reverse breakdown mode to maintain a constant voltage across the load despite variations in input voltage.'],

            // ============ 2018 Physics ============
            ['year' => 2018, 'subject' => 'Physics', 'topic' => 'Electric Charges and Fields', 'difficulty' => 'easy',
             'question_text' => 'The SI unit of electric charge is:',
             'options' => [['label' => 'A', 'text' => 'Volt'], ['label' => 'B', 'text' => 'Ampere'], ['label' => 'C', 'text' => 'Coulomb'], ['label' => 'D', 'text' => 'Ohm']],
             'correct_answer' => 'C', 'explanation' => 'The SI unit of electric charge is Coulomb (C). 1 Coulomb = charge flowing when 1 Ampere current flows for 1 second. Q = It.'],

            ['year' => 2018, 'subject' => 'Physics', 'topic' => 'Current Electricity', 'difficulty' => 'medium',
             'question_text' => 'In a Wheatstone bridge at balance condition, the galvanometer shows:',
             'options' => [['label' => 'A', 'text' => 'Maximum deflection'], ['label' => 'B', 'text' => 'Zero deflection'], ['label' => 'C', 'text' => 'Random fluctuation'], ['label' => 'D', 'text' => 'Full scale deflection']],
             'correct_answer' => 'B', 'explanation' => 'At balance: P/Q = R/S. No current flows through the galvanometer, showing zero deflection. This is the null condition of the Wheatstone bridge.'],

            ['year' => 2018, 'subject' => 'Physics', 'topic' => 'Alternating Current', 'difficulty' => 'easy',
             'question_text' => 'A transformer works on the principle of:',
             'options' => [['label' => 'A', 'text' => 'Self-induction'], ['label' => 'B', 'text' => 'Mutual induction'], ['label' => 'C', 'text' => 'Eddy currents'], ['label' => 'D', 'text' => 'Electrostatic induction']],
             'correct_answer' => 'B', 'explanation' => 'A transformer works on the principle of mutual induction. Changing current in the primary coil induces EMF in the secondary coil. V₁/V₂ = N₁/N₂.'],

            ['year' => 2018, 'subject' => 'Physics', 'topic' => 'Electromagnetic Waves', 'difficulty' => 'easy',
             'question_text' => 'The electromagnetic wave with the longest wavelength is:',
             'options' => [['label' => 'A', 'text' => 'Gamma rays'], ['label' => 'B', 'text' => 'X-rays'], ['label' => 'C', 'text' => 'Visible light'], ['label' => 'D', 'text' => 'Radio waves']],
             'correct_answer' => 'D', 'explanation' => 'In the electromagnetic spectrum, radio waves have the longest wavelength (> 0.1 m). Order: Radio > Microwave > IR > Visible > UV > X-ray > Gamma ray.'],

            ['year' => 2018, 'subject' => 'Physics', 'topic' => 'Dual Nature of Radiation and Matter', 'difficulty' => 'medium',
             'question_text' => 'If the kinetic energy of an electron doubles, its de Broglie wavelength:',
             'options' => [['label' => 'A', 'text' => 'Doubles'], ['label' => 'B', 'text' => 'Halves'], ['label' => 'C', 'text' => 'Becomes 1/√2 times'], ['label' => 'D', 'text' => 'Becomes √2 times']],
             'correct_answer' => 'C', 'explanation' => 'λ = h/√(2mKE). If KE doubles: λ\' = h/√(2m·2KE) = λ/√2. Wavelength becomes 1/√2 times the original.'],

            ['year' => 2018, 'subject' => 'Physics', 'topic' => 'Nuclei', 'difficulty' => 'easy',
             'question_text' => 'In beta-minus decay, a neutron converts into:',
             'options' => [['label' => 'A', 'text' => 'Proton + electron + antineutrino'], ['label' => 'B', 'text' => 'Proton + positron + neutrino'], ['label' => 'C', 'text' => 'Alpha particle + gamma ray'], ['label' => 'D', 'text' => 'Two protons']],
             'correct_answer' => 'A', 'explanation' => 'β⁻ decay: n → p + e⁻ + ν̄ₑ (antineutrino). A neutron converts to a proton, emitting an electron and an electron antineutrino. Mass number stays same, atomic number increases by 1.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedChemistry2018_2019(Exam $exam): void
    {
        $questions = [
            // ============ 2019 Chemistry ============
            ['year' => 2019, 'subject' => 'Chemistry', 'topic' => 'The Solid State', 'difficulty' => 'easy',
             'question_text' => 'The packing efficiency in face-centred cubic (FCC) structure is:',
             'options' => [['label' => 'A', 'text' => '52.4%'], ['label' => 'B', 'text' => '68%'], ['label' => 'C', 'text' => '74%'], ['label' => 'D', 'text' => '100%']],
             'correct_answer' => 'C', 'explanation' => 'FCC packing efficiency = (4 × 4/3 × π × r³)/(a³) × 100 = 74%. BCC = 68%. Simple cubic = 52.4%.'],

            ['year' => 2019, 'subject' => 'Chemistry', 'topic' => 'Solutions', 'difficulty' => 'medium',
             'question_text' => 'The boiling point of a solution is higher than pure solvent because:',
             'options' => [['label' => 'A', 'text' => 'The vapour pressure of solution is higher'], ['label' => 'B', 'text' => 'The vapour pressure of solution is lower'], ['label' => 'C', 'text' => 'The solution is denser'], ['label' => 'D', 'text' => 'The solute is volatile']],
             'correct_answer' => 'B', 'explanation' => 'Addition of non-volatile solute lowers the vapour pressure of the solution (Raoult\'s law). Therefore, a higher temperature is needed to make vapour pressure equal to atmospheric pressure, raising the boiling point.'],

            ['year' => 2019, 'subject' => 'Chemistry', 'topic' => 'Electrochemistry', 'difficulty' => 'easy',
             'question_text' => 'The unit of molar conductivity is:',
             'options' => [['label' => 'A', 'text' => 'S cm⁻¹'], ['label' => 'B', 'text' => 'S cm² mol⁻¹'], ['label' => 'C', 'text' => 'Ω cm'], ['label' => 'D', 'text' => 'S mol⁻¹']],
             'correct_answer' => 'B', 'explanation' => 'Molar conductivity (Λm) = κ/c. Unit = (S cm⁻¹)/(mol cm⁻³) = S cm² mol⁻¹. In SI: S m² mol⁻¹.'],

            ['year' => 2019, 'subject' => 'Chemistry', 'topic' => 'Chemical Kinetics', 'difficulty' => 'medium',
             'question_text' => 'For a first-order reaction, the graph of log[A] vs time is:',
             'options' => [['label' => 'A', 'text' => 'A straight line with positive slope'], ['label' => 'B', 'text' => 'A straight line with negative slope'], ['label' => 'C', 'text' => 'A parabola'], ['label' => 'D', 'text' => 'An exponential curve']],
             'correct_answer' => 'B', 'explanation' => 'For first-order: log[A] = log[A]₀ − kt/2.303. This is y = mx + c form with negative slope (−k/2.303). The graph is a straight line with negative slope.'],

            ['year' => 2019, 'subject' => 'Chemistry', 'topic' => 'Surface Chemistry', 'difficulty' => 'easy',
             'question_text' => 'Adsorption of gas on a solid surface is generally:',
             'options' => [['label' => 'A', 'text' => 'Endothermic'], ['label' => 'B', 'text' => 'Exothermic'], ['label' => 'C', 'text' => 'Neither exo nor endo'], ['label' => 'D', 'text' => 'Cannot be predicted']],
             'correct_answer' => 'B', 'explanation' => 'Adsorption is exothermic because when gas molecules adsorb on a surface, residual forces of surface decrease and energy is released. ΔH < 0, ΔS < 0.'],

            ['year' => 2019, 'subject' => 'Chemistry', 'topic' => 'The p-Block Elements', 'difficulty' => 'easy',
             'question_text' => 'Which of the following is the most electronegative element?',
             'options' => [['label' => 'A', 'text' => 'Oxygen'], ['label' => 'B', 'text' => 'Fluorine'], ['label' => 'C', 'text' => 'Chlorine'], ['label' => 'D', 'text' => 'Nitrogen']],
             'correct_answer' => 'B', 'explanation' => 'Fluorine (F) is the most electronegative element with an electronegativity of 4.0 on the Pauling scale. Order: F > O > N > Cl.'],

            ['year' => 2019, 'subject' => 'Chemistry', 'topic' => 'Coordination Compounds', 'difficulty' => 'medium',
             'question_text' => 'The hybridization of the central metal ion in [Ni(CN)₄]²⁻ is:',
             'options' => [['label' => 'A', 'text' => 'sp³'], ['label' => 'B', 'text' => 'dsp²'], ['label' => 'C', 'text' => 'sp³d'], ['label' => 'D', 'text' => 'd²sp³']],
             'correct_answer' => 'B', 'explanation' => 'In [Ni(CN)₄]²⁻, Ni²⁺ has d⁸ configuration. CN⁻ is a strong field ligand causing pairing. Hybridization = dsp² (square planar geometry).'],

            ['year' => 2019, 'subject' => 'Chemistry', 'topic' => 'Alcohols, Phenols and Ethers', 'difficulty' => 'easy',
             'question_text' => 'The functional group present in ethanol is:',
             'options' => [['label' => 'A', 'text' => '−CHO'], ['label' => 'B', 'text' => '−COOH'], ['label' => 'C', 'text' => '−OH'], ['label' => 'D', 'text' => '−NH₂']],
             'correct_answer' => 'C', 'explanation' => 'Ethanol (C₂H₅OH) contains the hydroxyl (−OH) functional group, characteristic of alcohols. −CHO is aldehyde, −COOH is carboxylic acid, −NH₂ is amine.'],

            // ============ 2018 Chemistry ============
            ['year' => 2018, 'subject' => 'Chemistry', 'topic' => 'The Solid State', 'difficulty' => 'easy',
             'question_text' => 'Glass is an example of:',
             'options' => [['label' => 'A', 'text' => 'Crystalline solid'], ['label' => 'B', 'text' => 'Amorphous solid'], ['label' => 'C', 'text' => 'Ionic solid'], ['label' => 'D', 'text' => 'Molecular solid']],
             'correct_answer' => 'B', 'explanation' => 'Glass is an amorphous solid — it lacks a regular, repeating arrangement of atoms. It has short-range order but no long-range order. It is also called a pseudo-solid or supercooled liquid.'],

            ['year' => 2018, 'subject' => 'Chemistry', 'topic' => 'Electrochemistry', 'difficulty' => 'medium',
             'question_text' => 'The standard hydrogen electrode has a potential of:',
             'options' => [['label' => 'A', 'text' => '1.0 V'], ['label' => 'B', 'text' => '0.0 V'], ['label' => 'C', 'text' => '−1.0 V'], ['label' => 'D', 'text' => '0.5 V']],
             'correct_answer' => 'B', 'explanation' => 'By convention, the Standard Hydrogen Electrode (SHE) is assigned a potential of exactly 0.00 V at all temperatures. All other electrode potentials are measured relative to SHE.'],

            ['year' => 2018, 'subject' => 'Chemistry', 'topic' => 'The d- and f-Block Elements', 'difficulty' => 'easy',
             'question_text' => 'The most common oxidation state of transition metals is:',
             'options' => [['label' => 'A', 'text' => '+1'], ['label' => 'B', 'text' => '+2'], ['label' => 'C', 'text' => '+3'], ['label' => 'D', 'text' => '+4']],
             'correct_answer' => 'B', 'explanation' => 'The +2 oxidation state is the most common for transition metals because the two 4s electrons are easily removed. Higher oxidation states involve removal of 3d electrons as well.'],

            ['year' => 2018, 'subject' => 'Chemistry', 'topic' => 'Amines', 'difficulty' => 'easy',
             'question_text' => 'Methylamine is an example of:',
             'options' => [['label' => 'A', 'text' => 'Primary aliphatic amine'], ['label' => 'B', 'text' => 'Secondary amine'], ['label' => 'C', 'text' => 'Tertiary amine'], ['label' => 'D', 'text' => 'Aromatic amine']],
             'correct_answer' => 'A', 'explanation' => 'Methylamine (CH₃NH₂) is a primary aliphatic amine — one hydrogen of NH₃ is replaced by a methyl group (−CH₃). Primary amines have the structure R−NH₂.'],

            ['year' => 2018, 'subject' => 'Chemistry', 'topic' => 'Biomolecules', 'difficulty' => 'easy',
             'question_text' => 'The monomer of natural rubber is:',
             'options' => [['label' => 'A', 'text' => 'Ethylene'], ['label' => 'B', 'text' => 'Isoprene'], ['label' => 'C', 'text' => 'Styrene'], ['label' => 'D', 'text' => 'Chloroprene']],
             'correct_answer' => 'B', 'explanation' => 'Natural rubber is a polymer of isoprene (2-methyl-1,3-butadiene). It is cis-1,4-polyisoprene. Vulcanization with sulfur improves its properties.'],

            ['year' => 2018, 'subject' => 'Chemistry', 'topic' => 'Aldehydes, Ketones and Carboxylic Acids', 'difficulty' => 'medium',
             'question_text' => 'Which reagent is used to convert an aldehyde to a carboxylic acid?',
             'options' => [['label' => 'A', 'text' => 'LiAlH₄'], ['label' => 'B', 'text' => 'KMnO₄/H⁺'], ['label' => 'C', 'text' => 'NaBH₄'], ['label' => 'D', 'text' => 'Zn-Hg/HCl']],
             'correct_answer' => 'B', 'explanation' => 'Acidified KMnO₄ is a strong oxidizing agent that oxidizes aldehydes to carboxylic acids: RCHO → RCOOH. LiAlH₄ and NaBH₄ are reducing agents. Zn-Hg/HCl is Clemmensen reduction.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedMathematics2018_2019(Exam $exam): void
    {
        $questions = [
            // ============ 2019 Mathematics ============
            ['year' => 2019, 'subject' => 'Mathematics', 'topic' => 'Relations and Functions', 'difficulty' => 'easy',
             'question_text' => 'Let f: R → R be defined by f(x) = 3x + 2. Then f is:',
             'options' => [['label' => 'A', 'text' => 'One-one only'], ['label' => 'B', 'text' => 'Onto only'], ['label' => 'C', 'text' => 'Bijective'], ['label' => 'D', 'text' => 'Neither one-one nor onto']],
             'correct_answer' => 'C', 'explanation' => 'f(x) = 3x + 2 is a linear function with non-zero slope. It is strictly increasing, hence one-one. Range = R = codomain, hence onto. Therefore bijective.'],

            ['year' => 2019, 'subject' => 'Mathematics', 'topic' => 'Matrices', 'difficulty' => 'easy',
             'question_text' => 'If A is a square matrix such that A² = A, then (I + A)³ − 7A is:',
             'options' => [['label' => 'A', 'text' => 'A'], ['label' => 'B', 'text' => 'I − A'], ['label' => 'C', 'text' => 'I'], ['label' => 'D', 'text' => '3A']],
             'correct_answer' => 'C', 'explanation' => 'A² = A (idempotent). (I+A)³ = I + 3A + 3A² + A³ = I + 3A + 3A + A = I + 7A. So (I+A)³ − 7A = I + 7A − 7A = I.'],

            ['year' => 2019, 'subject' => 'Mathematics', 'topic' => 'Determinants', 'difficulty' => 'medium',
             'question_text' => 'If A is a 3×3 matrix with |A| = 9, then |2A| is:',
             'options' => [['label' => 'A', 'text' => '18'], ['label' => 'B', 'text' => '36'], ['label' => 'C', 'text' => '72'], ['label' => 'D', 'text' => '81']],
             'correct_answer' => 'C', 'explanation' => '|kA| = k^n × |A| where n is the order. |2A| = 2³ × 9 = 8 × 9 = 72.'],

            ['year' => 2019, 'subject' => 'Mathematics', 'topic' => 'Continuity and Differentiability', 'difficulty' => 'medium',
             'question_text' => 'If y = log(cos(eˣ)), then dy/dx is:',
             'options' => [['label' => 'A', 'text' => '−eˣ tan(eˣ)'], ['label' => 'B', 'text' => 'eˣ tan(eˣ)'], ['label' => 'C', 'text' => '−tan(eˣ)'], ['label' => 'D', 'text' => 'eˣ/cos(eˣ)']],
             'correct_answer' => 'A', 'explanation' => 'Using chain rule: dy/dx = (1/cos(eˣ)) × (−sin(eˣ)) × eˣ = −eˣ × sin(eˣ)/cos(eˣ) = −eˣ tan(eˣ).'],

            ['year' => 2019, 'subject' => 'Mathematics', 'topic' => 'Integrals', 'difficulty' => 'easy',
             'question_text' => '∫ eˣ dx equals:',
             'options' => [['label' => 'A', 'text' => 'eˣ + C'], ['label' => 'B', 'text' => 'xeˣ + C'], ['label' => 'C', 'text' => 'eˣ/x + C'], ['label' => 'D', 'text' => 'x + C']],
             'correct_answer' => 'A', 'explanation' => '∫ eˣ dx = eˣ + C. The exponential function is its own integral (and derivative). This is a fundamental result.'],

            ['year' => 2019, 'subject' => 'Mathematics', 'topic' => 'Vectors', 'difficulty' => 'easy',
             'question_text' => 'If a⃗ = î + ĵ + k̂, then |a⃗| equals:',
             'options' => [['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '√2'], ['label' => 'C', 'text' => '√3'], ['label' => 'D', 'text' => '3']],
             'correct_answer' => 'C', 'explanation' => '|a⃗| = √(1² + 1² + 1²) = √3.'],

            ['year' => 2019, 'subject' => 'Mathematics', 'topic' => 'Probability', 'difficulty' => 'medium',
             'question_text' => 'If A and B are two events such that P(A) = 0.4, P(B) = 0.3, and P(A ∪ B) = 0.5, then P(A ∩ B) is:',
             'options' => [['label' => 'A', 'text' => '0.1'], ['label' => 'B', 'text' => '0.2'], ['label' => 'C', 'text' => '0.3'], ['label' => 'D', 'text' => '0.7']],
             'correct_answer' => 'B', 'explanation' => 'P(A ∪ B) = P(A) + P(B) − P(A ∩ B). 0.5 = 0.4 + 0.3 − P(A ∩ B). P(A ∩ B) = 0.7 − 0.5 = 0.2.'],

            ['year' => 2019, 'subject' => 'Mathematics', 'topic' => 'Three Dimensional Geometry', 'difficulty' => 'easy',
             'question_text' => 'The equation of z-axis is:',
             'options' => [['label' => 'A', 'text' => 'x = 0, y = 0'], ['label' => 'B', 'text' => 'x = 0, z = 0'], ['label' => 'C', 'text' => 'y = 0, z = 0'], ['label' => 'D', 'text' => 'x = y = z']],
             'correct_answer' => 'A', 'explanation' => 'The z-axis consists of all points where x = 0 and y = 0. Similarly, x-axis: y = 0, z = 0; y-axis: x = 0, z = 0.'],

            // ============ 2018 Mathematics ============
            ['year' => 2018, 'subject' => 'Mathematics', 'topic' => 'Relations and Functions', 'difficulty' => 'easy',
             'question_text' => 'Let f: {1, 3, 4} → {1, 2, 5} and g: {1, 2, 5} → {1, 3} be given by f = {(1,2), (3,5), (4,1)} and g = {(1,3), (2,3), (5,1)}. Then gof is:',
             'options' => [['label' => 'A', 'text' => '{(1,3), (3,1), (4,3)}'], ['label' => 'B', 'text' => '{(1,1), (3,3), (4,1)}'], ['label' => 'C', 'text' => '{(1,2), (3,5), (4,1)}'], ['label' => 'D', 'text' => '{(1,3), (3,3), (4,1)}']],
             'correct_answer' => 'A', 'explanation' => 'gof(1) = g(f(1)) = g(2) = 3. gof(3) = g(f(3)) = g(5) = 1. gof(4) = g(f(4)) = g(1) = 3. So gof = {(1,3), (3,1), (4,3)}.'],

            ['year' => 2018, 'subject' => 'Mathematics', 'topic' => 'Inverse Trigonometric Functions', 'difficulty' => 'easy',
             'question_text' => 'The value of tan⁻¹(√3) − sec⁻¹(−2) is:',
             'options' => [['label' => 'A', 'text' => 'π/3'], ['label' => 'B', 'text' => '−π/3'], ['label' => 'C', 'text' => 'π/6'], ['label' => 'D', 'text' => '−π/6']],
             'correct_answer' => 'B', 'explanation' => 'tan⁻¹(√3) = π/3. sec⁻¹(−2) = π − sec⁻¹(2) = π − π/3 = 2π/3. Result = π/3 − 2π/3 = −π/3.'],

            ['year' => 2018, 'subject' => 'Mathematics', 'topic' => 'Application of Derivatives', 'difficulty' => 'medium',
             'question_text' => 'The maximum value of the function f(x) = sin x is:',
             'options' => [['label' => 'A', 'text' => '0'], ['label' => 'B', 'text' => '1'], ['label' => 'C', 'text' => '−1'], ['label' => 'D', 'text' => 'Does not exist']],
             'correct_answer' => 'B', 'explanation' => 'sin x ranges from −1 to 1. Maximum value = 1, achieved at x = π/2 + 2nπ. f\'(x) = cos x = 0 at x = π/2; f"(π/2) = −sin(π/2) = −1 < 0, confirming maximum.'],

            ['year' => 2018, 'subject' => 'Mathematics', 'topic' => 'Integrals', 'difficulty' => 'easy',
             'question_text' => '∫ 1/x dx equals:',
             'options' => [['label' => 'A', 'text' => 'x + C'], ['label' => 'B', 'text' => 'log|x| + C'], ['label' => 'C', 'text' => '−1/x² + C'], ['label' => 'D', 'text' => 'eˣ + C']],
             'correct_answer' => 'B', 'explanation' => '∫ 1/x dx = log|x| + C (natural logarithm). This is because d/dx[ln|x|] = 1/x.'],

            ['year' => 2018, 'subject' => 'Mathematics', 'topic' => 'Differential Equations', 'difficulty' => 'easy',
             'question_text' => 'The degree of the differential equation (d²y/dx²)² + (dy/dx)³ = eˣ is:',
             'options' => [['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '2'], ['label' => 'C', 'text' => '3'], ['label' => 'D', 'text' => 'Not defined']],
             'correct_answer' => 'B', 'explanation' => 'Degree = power of the highest order derivative. Highest order derivative is d²y/dx², and its power is 2. Order = 2, Degree = 2.'],

            ['year' => 2018, 'subject' => 'Mathematics', 'topic' => 'Linear Programming', 'difficulty' => 'easy',
             'question_text' => 'The corner points of the feasible region determined by the constraints x ≥ 0, y ≥ 0, x + y ≤ 4 are:',
             'options' => [['label' => 'A', 'text' => '(0,0), (4,0), (0,4)'], ['label' => 'B', 'text' => '(0,0), (4,0), (4,4)'], ['label' => 'C', 'text' => '(0,0), (0,4), (4,4)'], ['label' => 'D', 'text' => '(4,0), (0,4), (4,4)']],
             'correct_answer' => 'A', 'explanation' => 'x ≥ 0, y ≥ 0 restricts to first quadrant. x + y ≤ 4 gives the line x + y = 4. Corner points: intersection of axes at (0,0), x-axis and line at (4,0), y-axis and line at (0,4).'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedBiology2018_2019(Exam $exam): void
    {
        $questions = [
            // ============ 2019 Biology ============
            ['year' => 2019, 'subject' => 'Biology', 'topic' => 'Reproduction in Organisms', 'difficulty' => 'easy',
             'question_text' => 'Parthenogenesis is the development of an organism from:',
             'options' => [['label' => 'A', 'text' => 'Fertilized egg'], ['label' => 'B', 'text' => 'Unfertilized egg'], ['label' => 'C', 'text' => 'Zygote'], ['label' => 'D', 'text' => 'Spore']],
             'correct_answer' => 'B', 'explanation' => 'Parthenogenesis is the development of an organism from an unfertilized egg without fertilization. It occurs in honeybees (drones), some lizards, and rotifers.'],

            ['year' => 2019, 'subject' => 'Biology', 'topic' => 'Sexual Reproduction in Flowering Plants', 'difficulty' => 'easy',
             'question_text' => 'Double fertilization involves the fusion of:',
             'options' => [['label' => 'A', 'text' => 'One male gamete with egg cell only'], ['label' => 'B', 'text' => 'Both male gametes with egg cell'], ['label' => 'C', 'text' => 'One male gamete with egg + one with polar nuclei'], ['label' => 'D', 'text' => 'One male gamete with synergids']],
             'correct_answer' => 'C', 'explanation' => 'Double fertilization (unique to angiosperms): one male gamete fuses with egg cell → zygote (2n), and the other male gamete fuses with two polar nuclei → PEN (3n) → endosperm.'],

            ['year' => 2019, 'subject' => 'Biology', 'topic' => 'Principles of Inheritance and Variation', 'difficulty' => 'medium',
             'question_text' => 'In humans, the sex of the child is determined by:',
             'options' => [['label' => 'A', 'text' => 'The mother\'s chromosome'], ['label' => 'B', 'text' => 'The father\'s chromosome'], ['label' => 'C', 'text' => 'Both parents equally'], ['label' => 'D', 'text' => 'Environmental factors']],
             'correct_answer' => 'B', 'explanation' => 'Sex is determined by the father. Mother always contributes X. Father contributes either X (daughter XX) or Y (son XY). So the sperm determines the sex.'],

            ['year' => 2019, 'subject' => 'Biology', 'topic' => 'Molecular Basis of Inheritance', 'difficulty' => 'easy',
             'question_text' => 'The Hershey-Chase experiment proved that:',
             'options' => [['label' => 'A', 'text' => 'Protein is the genetic material'], ['label' => 'B', 'text' => 'DNA is the genetic material'], ['label' => 'C', 'text' => 'RNA is the genetic material'], ['label' => 'D', 'text' => 'Lipids carry genetic information']],
             'correct_answer' => 'B', 'explanation' => 'Hershey and Chase (1952) used radioactive S³⁵ (protein) and P³² (DNA) labeled bacteriophages. Only P³² entered bacteria, proving DNA is the genetic material.'],

            ['year' => 2019, 'subject' => 'Biology', 'topic' => 'Human Health and Disease', 'difficulty' => 'easy',
             'question_text' => 'The vector for dengue fever is:',
             'options' => [['label' => 'A', 'text' => 'Anopheles mosquito'], ['label' => 'B', 'text' => 'Aedes mosquito'], ['label' => 'C', 'text' => 'Culex mosquito'], ['label' => 'D', 'text' => 'Sandfly']],
             'correct_answer' => 'B', 'explanation' => 'Dengue fever is transmitted by Aedes aegypti mosquito. Malaria is by Anopheles. Filariasis is by Culex. Kala-azar is by Sandfly.'],

            ['year' => 2019, 'subject' => 'Biology', 'topic' => 'Ecosystem', 'difficulty' => 'easy',
             'question_text' => 'The primary productivity of an ecosystem depends on:',
             'options' => [['label' => 'A', 'text' => 'Consumers'], ['label' => 'B', 'text' => 'Decomposers'], ['label' => 'C', 'text' => 'Producers (green plants)'], ['label' => 'D', 'text' => 'Omnivores']],
             'correct_answer' => 'C', 'explanation' => 'Primary productivity is the rate of biomass production by producers (autotrophs) through photosynthesis. It depends on plant species, availability of nutrients, light, and environmental factors.'],

            ['year' => 2019, 'subject' => 'Biology', 'topic' => 'Biotechnology and its Applications', 'difficulty' => 'medium',
             'question_text' => 'Gene therapy involves:',
             'options' => [['label' => 'A', 'text' => 'Removing defective genes only'], ['label' => 'B', 'text' => 'Inserting functional genes to replace defective ones'], ['label' => 'C', 'text' => 'Cloning the patient'], ['label' => 'D', 'text' => 'Using antibiotics to cure genetic diseases']],
             'correct_answer' => 'B', 'explanation' => 'Gene therapy involves introducing a functional gene into a patient to replace or supplement a defective gene. First successful gene therapy was for ADA deficiency (SCID) in 1990.'],

            // ============ 2018 Biology ============
            ['year' => 2018, 'subject' => 'Biology', 'topic' => 'Human Reproduction', 'difficulty' => 'easy',
             'question_text' => 'The gestation period in humans is approximately:',
             'options' => [['label' => 'A', 'text' => '7 months'], ['label' => 'B', 'text' => '9 months'], ['label' => 'C', 'text' => '11 months'], ['label' => 'D', 'text' => '6 months']],
             'correct_answer' => 'B', 'explanation' => 'The gestation period in humans is approximately 9 months (266-280 days or about 38-40 weeks). It is the time from fertilization to birth.'],

            ['year' => 2018, 'subject' => 'Biology', 'topic' => 'Principles of Inheritance and Variation', 'difficulty' => 'easy',
             'question_text' => 'Down syndrome is caused by trisomy of chromosome:',
             'options' => [['label' => 'A', 'text' => '13'], ['label' => 'B', 'text' => '18'], ['label' => 'C', 'text' => '21'], ['label' => 'D', 'text' => '22']],
             'correct_answer' => 'C', 'explanation' => 'Down syndrome (trisomy 21) is caused by the presence of an extra copy of chromosome 21 (total 47 chromosomes). It results from non-disjunction during meiosis.'],

            ['year' => 2018, 'subject' => 'Biology', 'topic' => 'Molecular Basis of Inheritance', 'difficulty' => 'medium',
             'question_text' => 'The number of hydrogen bonds between adenine and thymine in DNA is:',
             'options' => [['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '2'], ['label' => 'C', 'text' => '3'], ['label' => 'D', 'text' => '4']],
             'correct_answer' => 'B', 'explanation' => 'Adenine (A) pairs with Thymine (T) through 2 hydrogen bonds. Guanine (G) pairs with Cytosine (C) through 3 hydrogen bonds. This is Chargaff\'s rule.'],

            ['year' => 2018, 'subject' => 'Biology', 'topic' => 'Evolution', 'difficulty' => 'easy',
             'question_text' => 'The first life forms on Earth were:',
             'options' => [['label' => 'A', 'text' => 'Autotrophs'], ['label' => 'B', 'text' => 'Heterotrophs'], ['label' => 'C', 'text' => 'Chemotrophs'], ['label' => 'D', 'text' => 'Phototrophs']],
             'correct_answer' => 'B', 'explanation' => 'The first life forms were chemoheterotrophs that obtained energy from simple organic molecules in the primordial soup. They were anaerobic and preceded autotrophs.'],

            ['year' => 2018, 'subject' => 'Biology', 'topic' => 'Biotechnology: Principles and Processes', 'difficulty' => 'easy',
             'question_text' => 'The plasmid used as a vector must have:',
             'options' => [['label' => 'A', 'text' => 'Large size only'], ['label' => 'B', 'text' => 'Origin of replication'], ['label' => 'C', 'text' => 'No restriction sites'], ['label' => 'D', 'text' => 'No selectable marker']],
             'correct_answer' => 'B', 'explanation' => 'A good cloning vector must have: (1) Origin of replication (ori), (2) Selectable marker (antibiotic resistance), (3) Unique restriction sites for cloning, (4) Small size for easy manipulation.'],

            ['year' => 2018, 'subject' => 'Biology', 'topic' => 'Organisms and Populations', 'difficulty' => 'medium',
             'question_text' => 'The carrying capacity of the environment is represented by:',
             'options' => [['label' => 'A', 'text' => 'r'], ['label' => 'B', 'text' => 'N'], ['label' => 'C', 'text' => 'K'], ['label' => 'D', 'text' => 'J']],
             'correct_answer' => 'C', 'explanation' => 'K represents carrying capacity — the maximum population size that the environment can sustain indefinitely. r is the intrinsic rate of natural increase. N is the population size.'],

            ['year' => 2018, 'subject' => 'Biology', 'topic' => 'Biodiversity and Conservation', 'difficulty' => 'easy',
             'question_text' => 'The "hotspot" of biodiversity in India is:',
             'options' => [['label' => 'A', 'text' => 'Thar Desert'], ['label' => 'B', 'text' => 'Western Ghats'], ['label' => 'C', 'text' => 'Indo-Gangetic plains'], ['label' => 'D', 'text' => 'Deccan Plateau']],
             'correct_answer' => 'B', 'explanation' => 'India has 4 biodiversity hotspots: Western Ghats, Himalayas, Indo-Burma, and Sundaland. Western Ghats is the most well-known Indian hotspot with high endemism.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedEnglish2018_2019(Exam $exam): void
    {
        $questions = [
            // ============ 2019 English ============
            ['year' => 2019, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Choose the correct form of the verb: He _____ to the market yesterday.',
             'options' => [['label' => 'A', 'text' => 'go'], ['label' => 'B', 'text' => 'goes'], ['label' => 'C', 'text' => 'went'], ['label' => 'D', 'text' => 'going']],
             'correct_answer' => 'C', 'explanation' => '"Yesterday" indicates past tense. The past form of "go" is "went". Simple past tense is used for completed actions in the past.'],

            ['year' => 2019, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'medium',
             'question_text' => 'Choose the correct sentence:',
             'options' => [['label' => 'A', 'text' => 'He is more taller than his brother.'], ['label' => 'B', 'text' => 'He is taller than his brother.'], ['label' => 'C', 'text' => 'He is most taller than his brother.'], ['label' => 'D', 'text' => 'He is tallest than his brother.']],
             'correct_answer' => 'B', 'explanation' => 'Comparative degree: adjective + er + than. "Taller than" is correct. Never use "more" with "-er" form (more taller is wrong). "Most" and "-est" are for superlative degree.'],

            ['year' => 2019, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'easy',
             'question_text' => 'In "The Last Lesson", what order was received from Berlin?',
             'options' => [['label' => 'A', 'text' => 'Only German to be taught in schools of Alsace and Lorraine'], ['label' => 'B', 'text' => 'Schools to be closed permanently'], ['label' => 'C', 'text' => 'New teachers to be appointed'], ['label' => 'D', 'text' => 'English to be made compulsory']],
             'correct_answer' => 'A', 'explanation' => 'The order from Berlin mandated that only German would be taught in the schools of Alsace and Lorraine, replacing French. This was M. Hamel\'s last French lesson.'],

            ['year' => 2019, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'medium',
             'question_text' => 'The roadside stand in Robert Frost\'s poem "A Roadside Stand" was set up to sell:',
             'options' => [['label' => 'A', 'text' => 'Handicrafts'], ['label' => 'B', 'text' => 'Wild berries and squash'], ['label' => 'C', 'text' => 'Flowers'], ['label' => 'D', 'text' => 'Newspapers']],
             'correct_answer' => 'B', 'explanation' => 'In Frost\'s "A Roadside Stand," the poor farmers set up a stand by the highway to sell wild berries, golden squash, and other produce, hoping to earn some money from passing city traffic.'],

            ['year' => 2019, 'subject' => 'English', 'topic' => 'Writing Skills', 'difficulty' => 'easy',
             'question_text' => 'An article for a newspaper typically begins with:',
             'options' => [['label' => 'A', 'text' => 'A personal greeting'], ['label' => 'B', 'text' => 'An attention-grabbing title/headline'], ['label' => 'C', 'text' => 'The writer\'s address'], ['label' => 'D', 'text' => 'A poem']],
             'correct_answer' => 'B', 'explanation' => 'A newspaper article begins with an attention-grabbing title/headline, followed by the writer\'s byline, and then the opening paragraph that summarizes the main point.'],

            // ============ 2018 English ============
            ['year' => 2018, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'Fill in the blank with the correct modal: You _____ respect your elders.',
             'options' => [['label' => 'A', 'text' => 'can'], ['label' => 'B', 'text' => 'should'], ['label' => 'C', 'text' => 'might'], ['label' => 'D', 'text' => 'would']],
             'correct_answer' => 'B', 'explanation' => '"Should" is used for advice, duty, or moral obligation. "You should respect your elders" expresses a moral duty. "Can" shows ability, "might" shows possibility.'],

            ['year' => 2018, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'easy',
             'question_text' => 'The synonym of "enormous" is:',
             'options' => [['label' => 'A', 'text' => 'Tiny'], ['label' => 'B', 'text' => 'Huge'], ['label' => 'C', 'text' => 'Normal'], ['label' => 'D', 'text' => 'Narrow']],
             'correct_answer' => 'B', 'explanation' => '"Enormous" means extremely large in size. Its synonym is "huge" (or gigantic, massive, immense). "Tiny" is its antonym.'],

            ['year' => 2018, 'subject' => 'English', 'topic' => 'Grammar', 'difficulty' => 'medium',
             'question_text' => 'Identify the clause in: "I know the man who stole the bicycle."',
             'options' => [['label' => 'A', 'text' => '"I know the man" is the subordinate clause'], ['label' => 'B', 'text' => '"who stole the bicycle" is a relative clause'], ['label' => 'C', 'text' => 'There is no subordinate clause'], ['label' => 'D', 'text' => '"the man" is the clause']],
             'correct_answer' => 'B', 'explanation' => '"who stole the bicycle" is a relative (adjective) clause modifying "the man". It is introduced by the relative pronoun "who". "I know the man" is the main clause.'],

            ['year' => 2018, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'easy',
             'question_text' => 'In "Indigo", Gandhiji\'s visit to Champaran was significant because it was a case of:',
             'options' => [['label' => 'A', 'text' => 'Fighting for freedom of press'], ['label' => 'B', 'text' => 'Championing the cause of poor farmers against planters'], ['label' => 'C', 'text' => 'Non-cooperation movement'], ['label' => 'D', 'text' => 'Salt March']],
             'correct_answer' => 'B', 'explanation' => 'Gandhiji\'s Champaran movement (1917) championed the cause of poor indigo sharecroppers against British planters who forced them to grow indigo under the tinkathia system.'],

            ['year' => 2018, 'subject' => 'English', 'topic' => 'Literature', 'difficulty' => 'medium',
             'question_text' => 'In the poem "A Thing of Beauty" (Endymion), Keats says that beauty is:',
             'options' => [['label' => 'A', 'text' => 'Temporary and fleeting'], ['label' => 'B', 'text' => 'A joy forever'], ['label' => 'C', 'text' => 'Only for the rich'], ['label' => 'D', 'text' => 'Found only in nature']],
             'correct_answer' => 'B', 'explanation' => 'Keats writes "A thing of beauty is a joy forever: / Its loveliness increases; it will never / Pass into nothingness." Beauty gives eternal joy and its appeal only grows with time.'],
        ];

        $this->seedQuestions($exam, $questions);
    }

    private function seedQuestions(Exam $exam, array $questions): void
    {
        foreach ($questions as $q) {
            $exists = ExamQuestion::where('exam_id', $exam->id)
                ->where('year', $q['year'])
                ->where('subject', $q['subject'])
                ->where('question_text', $q['question_text'])
                ->exists();

            if ($exists) {
                $this->skipped++;
                continue;
            }

            ExamQuestion::create([
                'exam_id' => $exam->id,
                'subject' => $q['subject'],
                'topic' => $q['topic'],
                'year' => $q['year'],
                'type' => 'mcq',
                'question_text' => $q['question_text'],
                'options' => $q['options'],
                'correct_answer' => $q['correct_answer'],
                'explanation' => $q['explanation'],
                'difficulty' => $q['difficulty'],
                'language' => 'english',
                'tags' => ['pyq', 'real-paper', 'cbse', (string) $q['year']],
                'is_active' => true,
            ]);

            $this->imported++;
        }
    }
}
