import React from 'react';
import Svg, { Path, Rect } from 'react-native-svg';

const CalculatorIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Rect
      x="4"
      y="2"
      width="16"
      height="20"
      rx="2"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Rect
      x="7"
      y="5"
      width="10"
      height="4"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M7 13h2M11 13h2M15 13h2M7 17h2M11 17h2M15 17v-4"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default CalculatorIcon;
