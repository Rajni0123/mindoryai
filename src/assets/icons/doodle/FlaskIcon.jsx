import React from 'react';
import Svg, { Path } from 'react-native-svg';

const FlaskIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Path
      d="M9 3h6M10 3v6l-5 9c-.5 1 .2 2 1.3 2h11.4c1.1 0 1.8-1 1.3-2l-5-9V3"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M7 15h10"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M9 18c.5 0 1-.5 1.5-.5s1 .5 1.5.5 1-.5 1.5-.5 1 .5 1.5.5"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default FlaskIcon;
