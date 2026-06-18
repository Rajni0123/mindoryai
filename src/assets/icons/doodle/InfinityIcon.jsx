import React from 'react';
import Svg, { Path } from 'react-native-svg';

const InfinityIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 12c-2-3-4-5-6-5s-4 2-4 5 2 5 4 5 4-2 6-5c2 3 4 5 6 5s4-2 4-5-2-5-4-5-4 2-6 5z"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default InfinityIcon;
