import React from 'react';
import Svg, { Path } from 'react-native-svg';

const FireIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 22c4-2 7-5 7-10 0-3-2-5-3-7-1 2-2 3-3 3-1-3-2-6-4-8 0 4-1 6-3 8-2 2-3 4-3 7 0 5 3 8 7 10"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M12 22c-2-1-3-3-3-5 0-2 1-3 2-4 .5 1 1 2 2 2s1.5-1 2-2c1 1 2 2 2 4 0 2-1 4-3 5"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default FireIcon;
