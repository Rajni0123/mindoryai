import React from 'react';
import Svg, { Path } from 'react-native-svg';

const ScrollIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M8 3c-2 0-3 1-3 2s1 2 3 2h10c2 0 3-1 3-2s-1-2-3-2H8z"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M5 5v14c0 1 1 2 3 2h10c-2 0-3-1-3-2V7"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M18 19c0 1 1 2 3 2v-2c0-1-1-2-3-2v2z"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M8 10h6M8 14h4"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default ScrollIcon;
