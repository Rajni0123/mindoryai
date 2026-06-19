import React from 'react';
import Svg, { Path } from 'react-native-svg';

const BrainIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M12 4c-2 0-4 1-4 3 0 1 .5 2 1 2.5C7.5 10 6 11.5 6 14c0 2.5 2 4 4 4h1"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M12 4c2 0 4 1 4 3 0 1-.5 2-1 2.5C16.5 10 18 11.5 18 14c0 2.5-2 4-4 4h-1"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M12 4v4M8 11h2M14 11h2"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M12 18v4M10 20h4"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default BrainIcon;
