import React from 'react';
import Svg, { Path } from 'react-native-svg';

const StarIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 2l3 6 6 1-4.5 4 1 6.5-5.5-3-5.5 3 1-6.5L3 9l6-1 3-6z"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default StarIcon;
