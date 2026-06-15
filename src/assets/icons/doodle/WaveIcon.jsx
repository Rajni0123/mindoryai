import React from 'react';
import Svg, { Path } from 'react-native-svg';

const WaveIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M2 12c1.5-3 3-5 4.5-5s3 2 4.5 5 3 5 4.5 5 3-2 4.5-5"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M2 17c1.5-2 3-3 4.5-3s3 1 4.5 3 3 3 4.5 3 3-1 4.5-3"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      opacity={0.5}
    />
  </Svg>
);

export default WaveIcon;
