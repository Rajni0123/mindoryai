import React from 'react';
import Svg, { Path } from 'react-native-svg';

const GraphIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M3 3v18h18"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M6 17l4-6 4 3 5-8"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M17 8l2-2M19 6v4h-4"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default GraphIcon;
