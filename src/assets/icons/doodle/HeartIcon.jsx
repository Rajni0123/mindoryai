import React from 'react';
import Svg, { Path } from 'react-native-svg';

const HeartIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 21C12 21 4 15 4 9c0-3 2.5-5 5-5 1.5 0 2.5.5 3 1.5.5-1 1.5-1.5 3-1.5 2.5 0 5 2 5 5 0 6-8 12-8 12z"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M12 7v4M12 11l-2 2M12 11l2 2"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default HeartIcon;
