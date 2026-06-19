import React from 'react';
import { Text, View, StyleSheet } from 'react-native';
import DoodleIcon from './DoodleIcon';

/**
 * RichTextRenderer Component
 * Parses text with inline icons, bold text, and equation styling
 *
 * Supported syntax:
 * - Icons: <icon name="sun"/> or <icon name="sun" />
 * - Bold: **bold text**
 * - Equations: $E = mc^2$ (rendered in italic monospace)
 * - Subscript hint: CO₂ (use unicode subscripts)
 * - Superscript hint: m² (use unicode superscripts)
 *
 * @param {Object} props
 * @param {string} props.text - Text to render with special syntax
 * @param {Object} [props.style] - Text styles
 * @param {number} [props.iconSize=18] - Size for inline icons
 * @param {string} [props.iconColor='#2D2D2D'] - Color for inline icons
 * @param {Object} [props.boldStyle] - Styles for bold text
 * @param {Object} [props.equationStyle] - Styles for equations
 *
 * @example
 * <RichTextRenderer
 *   text="The <icon name='sun'/> provides **energy** for $E = mc^2$"
 *   style={{ fontSize: 16 }}
 * />
 */
const RichTextRenderer = ({
  text,
  style,
  iconSize = 18,
  iconColor = '#2D2D2D',
  boldStyle,
  equationStyle,
}) => {
  if (!text) return null;

  // Parse the text and return array of components
  const parseText = (inputText) => {
    const elements = [];
    let currentIndex = 0;
    let keyIndex = 0;

    // Combined regex for all patterns
    // Group 1: icon tag, Group 2: icon name
    // Group 3: bold text (content inside **)
    // Group 4: equation (content inside $)
    const combinedRegex = /(<icon\s+name=["']([^"']+)["']\s*\/?>)|(\*\*([^*]+)\*\*)|(\$([^$]+)\$)/g;

    let match;
    while ((match = combinedRegex.exec(inputText)) !== null) {
      // Add text before the match
      if (match.index > currentIndex) {
        const textBefore = inputText.slice(currentIndex, match.index);
        if (textBefore) {
          elements.push(
            <Text key={`text-${keyIndex++}`} style={[styles.text, style]}>
              {textBefore}
            </Text>
          );
        }
      }

      // Check which pattern matched
      if (match[1]) {
        // Icon match
        const iconName = match[2];
        elements.push(
          <View key={`icon-${keyIndex++}`} style={styles.iconWrapper}>
            <DoodleIcon
              name={iconName}
              size={iconSize}
              color={iconColor}
              inline
            />
          </View>
        );
      } else if (match[3]) {
        // Bold match
        const boldText = match[4];
        elements.push(
          <Text
            key={`bold-${keyIndex++}`}
            style={[styles.text, style, styles.bold, boldStyle]}
          >
            {boldText}
          </Text>
        );
      } else if (match[5]) {
        // Equation match
        const equationText = match[6];
        elements.push(
          <Text
            key={`equation-${keyIndex++}`}
            style={[styles.text, style, styles.equation, equationStyle]}
          >
            {equationText}
          </Text>
        );
      }

      currentIndex = match.index + match[0].length;
    }

    // Add remaining text
    if (currentIndex < inputText.length) {
      const remainingText = inputText.slice(currentIndex);
      if (remainingText) {
        elements.push(
          <Text key={`text-${keyIndex++}`} style={[styles.text, style]}>
            {remainingText}
          </Text>
        );
      }
    }

    return elements;
  };

  const parsedElements = parseText(text);

  // If no special elements found, return simple text
  if (parsedElements.length === 0) {
    return <Text style={[styles.text, style]}>{text}</Text>;
  }

  return (
    <Text style={[styles.container, style]}>
      {parsedElements}
    </Text>
  );
};

/**
 * Helper function to create icon syntax
 * @param {string} name - Icon name
 * @returns {string} Icon syntax string
 */
export const icon = (name) => `<icon name="${name}"/>`;

/**
 * Helper function to create bold syntax
 * @param {string} text - Text to make bold
 * @returns {string} Bold syntax string
 */
export const bold = (text) => `**${text}**`;

/**
 * Helper function to create equation syntax
 * @param {string} equation - Equation text
 * @returns {string} Equation syntax string
 */
export const equation = (eq) => `$${eq}$`;

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
  },
  text: {
    fontSize: 16,
    color: '#2D2D2D',
    lineHeight: 24,
  },
  iconWrapper: {
    alignItems: 'center',
    justifyContent: 'center',
    marginHorizontal: 2,
  },
  bold: {
    fontWeight: '700',
  },
  equation: {
    fontFamily: 'monospace',
    fontStyle: 'italic',
    backgroundColor: '#F5F5F5',
    paddingHorizontal: 4,
    borderRadius: 4,
  },
});

export default RichTextRenderer;
