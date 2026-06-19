import React from 'react';
import Svg, { Path, Circle } from 'react-native-svg';

const CompassIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Circle
      cx="12"
      cy="12"
      r="9"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M16 8l-5 3-3 5 5-3 3-5z"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Circle
      cx="12"
      cy="12"
      r="1"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M12 3v1M12 20v1M3 12h1M20 12h1"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      opacity={0.5}
    />
  </Svg>
);

export default CompassIcon;
