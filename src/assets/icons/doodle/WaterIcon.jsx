import React from 'react';
import Svg, { Path } from 'react-native-svg';

const WaterIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 2c0 0-6 7-6 12 0 3.3 2.7 6 6 6s6-2.7 6-6c0-5-6-12-6-12z"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M9 16c0 1.5 1.3 3 3 3"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default WaterIcon;
