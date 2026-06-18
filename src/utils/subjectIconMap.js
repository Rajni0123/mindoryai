/**
 * Subject Icon Mapping
 * Maps icon names to their corresponding React components
 * Used by DoodleIcon component for rendering
 */

import {
  // Physics
  SunIcon,
  AtomIcon,
  MagnetIcon,
  WaveIcon,
  BatteryIcon,
  EnergyIcon,
  BulbIcon,
  // Chemistry
  FlaskIcon,
  MoleculeIcon,
  WaterIcon,
  FireIcon,
  CrystalIcon,
  // Biology
  DnaIcon,
  CellIcon,
  HeartIcon,
  PlantIcon,
  BrainIcon,
  LeafIcon,
  // Maths
  GraphIcon,
  TriangleIcon,
  CircleIcon,
  CalculatorIcon,
  InfinityIcon,
  // History
  CrownIcon,
  SwordIcon,
  ScrollIcon,
  // Geography
  MountainIcon,
  RiverIcon,
  CompassIcon,
  CloudIcon,
  VolcanoIcon,
  // Common
  StarIcon,
  WarningIcon,
  NoteIcon,
  TickIcon,
  CrossIcon,
  ArrowIcon,
  FormulaIcon,
} from '../assets/icons/doodle';

/**
 * Icon name to component mapping
 * Usage: iconMap['sun'] returns SunIcon component
 */
export const iconMap = {
  // Physics icons
  sun: SunIcon,
  atom: AtomIcon,
  magnet: MagnetIcon,
  wave: WaveIcon,
  battery: BatteryIcon,
  energy: EnergyIcon,
  bulb: BulbIcon,
  lightning: EnergyIcon, // alias

  // Chemistry icons
  flask: FlaskIcon,
  molecule: MoleculeIcon,
  water: WaterIcon,
  fire: FireIcon,
  crystal: CrystalIcon,
  beaker: FlaskIcon, // alias
  droplet: WaterIcon, // alias

  // Biology icons
  dna: DnaIcon,
  cell: CellIcon,
  heart: HeartIcon,
  plant: PlantIcon,
  brain: BrainIcon,
  leaf: LeafIcon,
  seedling: PlantIcon, // alias

  // Maths icons
  graph: GraphIcon,
  triangle: TriangleIcon,
  circle: CircleIcon,
  calculator: CalculatorIcon,
  infinity: InfinityIcon,
  chart: GraphIcon, // alias

  // History icons
  crown: CrownIcon,
  sword: SwordIcon,
  scroll: ScrollIcon,
  king: CrownIcon, // alias

  // Geography icons
  mountain: MountainIcon,
  river: RiverIcon,
  compass: CompassIcon,
  cloud: CloudIcon,
  volcano: VolcanoIcon,
  weather: CloudIcon, // alias

  // Common icons
  star: StarIcon,
  warning: WarningIcon,
  note: NoteIcon,
  tick: TickIcon,
  cross: CrossIcon,
  arrow: ArrowIcon,
  formula: FormulaIcon,
  check: TickIcon, // alias
  x: CrossIcon, // alias
  alert: WarningIcon, // alias
  important: WarningIcon, // alias
};

/**
 * Get all available icon names
 * @returns {string[]} Array of icon names
 */
export const getIconNames = () => Object.keys(iconMap);

/**
 * Check if an icon exists
 * @param {string} name - Icon name
 * @returns {boolean}
 */
export const hasIcon = (name) => name in iconMap;

/**
 * Subject-wise icon grouping for reference
 */
export const subjectIcons = {
  physics: ['sun', 'atom', 'magnet', 'wave', 'battery', 'energy', 'bulb'],
  chemistry: ['flask', 'molecule', 'water', 'fire', 'crystal'],
  biology: ['dna', 'cell', 'heart', 'plant', 'brain', 'leaf'],
  maths: ['graph', 'triangle', 'circle', 'calculator', 'infinity'],
  history: ['crown', 'sword', 'scroll'],
  geography: ['mountain', 'river', 'compass', 'cloud', 'volcano'],
  common: ['star', 'warning', 'note', 'tick', 'cross', 'arrow', 'formula'],
};

export default iconMap;
