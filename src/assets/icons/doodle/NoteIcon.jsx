import React from 'react';
import Svg, { Path, Rect } from 'react-native-svg';

const NoteIcon = ({ size = 24, color = '#2D2D2D', strokeWidth = 2.5 }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    <Rect
      x="4"
      y="3"
      width="16"
      height="18"
      rx="2"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M8 7h8M8 11h8M8 15h5"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
    <Path
      d="M15 15l2 2 2-4"
      stroke={color}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  </Svg>
);

export default NoteIcon;
