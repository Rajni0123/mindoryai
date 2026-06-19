import React from 'react';
import { View, StyleSheet } from 'react-native';
import { iconMap, hasIcon } from '../utils/subjectIconMap';

/**
 * DoodleIcon Component
 * Renders hand-drawn style SVG icons by name
 *
 * @param {Object} props
 * @param {string} props.name - Icon name (e.g., 'sun', 'atom', 'flask')
 * @param {number} [props.size=24] - Icon size in pixels
 * @param {string} [props.color='#2D2D2D'] - Stroke color
 * @param {number} [props.strokeWidth=2.5] - Stroke width
 * @param {Object} [props.style] - Additional container styles
 * @param {boolean} [props.inline=false] - Whether icon is inline with text
 *
 * @example
 * <DoodleIcon name="sun" size={32} color="#FF6B6B" />
 * <DoodleIcon name="flask" inline />
 */
const DoodleIcon = ({
  name,
  size = 24,
  color = '#2D2D2D',
  strokeWidth = 2.5,
  style,
  inline = false,
}) => {
  // Get the icon component from the map
  const IconComponent = iconMap[name?.toLowerCase()];

  // Return null if icon doesn't exist
  if (!IconComponent) {
    if (__DEV__) {
      console.warn(`DoodleIcon: Icon "${name}" not found. Available icons:`, Object.keys(iconMap));
    }
    return null;
  }

  // Render inline (for text flow)
  if (inline) {
    return (
      <View style={[styles.inlineContainer, { width: size, height: size }, style]}>
        <IconComponent size={size} color={color} strokeWidth={strokeWidth} />
      </View>
    );
  }

  // Render with wrapper
  return (
    <View style={[styles.container, style]}>
      <IconComponent size={size} color={color} strokeWidth={strokeWidth} />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  inlineContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    marginHorizontal: 2,
  },
});

/**
 * Get all available icon names
 */
DoodleIcon.getAvailableIcons = () => Object.keys(iconMap);

/**
 * Check if an icon exists
 */
DoodleIcon.hasIcon = hasIcon;

export default DoodleIcon;
