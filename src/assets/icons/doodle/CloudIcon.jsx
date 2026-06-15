import React from 'react';
import Svg, { Path } from 'react-native-svg';

const CloudIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M6 19h12c2.5 0 4-1.5 4-4s-1.5-4-4-4c0-3-2-5-5-5-2.5 0-4.5 1.5-5 4-2.5 0-4 2-4 4s1 5 2 5z"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default CloudIcon;
