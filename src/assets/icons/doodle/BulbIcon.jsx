import React from 'react';
import Svg, { Path, Circle } from 'react-native-svg';

const BulbIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M9 18h6M10 22h4"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M9 18v-2c-2-1.5-3-3.5-3-6 0-4 3-7 6-7s6 3 6 7c0 2.5-1 4.5-3 6v2"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M12 3v1M5.5 5.5l.7.7M3 12h1M5.5 18.5l.7-.7"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      opacity={0.6}
    />
  </Svg>
);

export default BulbIcon;
