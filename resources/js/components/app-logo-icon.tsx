import { useEffect, useId, useState } from 'react';
import type { SVGAttributes } from 'react';

const monthFormatter = new Intl.DateTimeFormat('en-US', { month: 'long' });
const weekdayFormatter = new Intl.DateTimeFormat('en-US', { weekday: 'short' });

const SANS_FONT =
    "'Instrument Sans', ui-sans-serif, system-ui, -apple-system, sans-serif";
const SERIF_FONT = "'Fraunces', Georgia, 'Times New Roman', serif";

const RING_X = [120, 190, 246, 302, 372];

function getCurrentDateParts(): {
    day: string;
    weekday: string;
    month: string;
} {
    const now = new Date();

    return {
        day: String(now.getDate()).padStart(2, '0'),
        weekday: weekdayFormatter.format(now).toUpperCase(),
        month: monthFormatter.format(now).toUpperCase(),
    };
}

type AppLogoIconProps = SVGAttributes<SVGSVGElement> & { compact?: boolean };

export default function AppLogoIcon({
    compact = false,
    ...props
}: AppLogoIconProps) {
    const shadowId = useId().replaceAll(':', '');
    const [{ day, weekday, month }, setDateParts] =
        useState(getCurrentDateParts);

    useEffect(() => {
        const intervalId = window.setInterval(() => {
            setDateParts(getCurrentDateParts());
        }, 60_000);

        return () => window.clearInterval(intervalId);
    }, []);

    const label = `Almanac calendar icon showing ${weekday} ${month} ${day}`;

    return (
        <svg
            {...props}
            viewBox="0 0 512 512"
            xmlns="http://www.w3.org/2000/svg"
            aria-label={label}
        >
            <title>{label}</title>
            <defs>
                <filter
                    id={shadowId}
                    x="-14%"
                    y="-10%"
                    width="128%"
                    height="130%"
                    colorInterpolationFilters="sRGB"
                >
                    <feDropShadow
                        dx="0"
                        dy="18"
                        stdDeviation="20"
                        floodColor="#2a2417"
                        floodOpacity="0.20"
                    />
                    <feDropShadow
                        dx="0"
                        dy="3"
                        stdDeviation="4"
                        floodColor="#2a2417"
                        floodOpacity="0.12"
                    />
                </filter>
            </defs>

            <g filter={`url(#${shadowId})`}>
                <rect
                    x="46"
                    y="86"
                    width="420"
                    height="396"
                    rx="44"
                    fill="#FBF7EC"
                />
                <path
                    d="M46 130a44 44 0 0 1 44-44h332a44 44 0 0 1 44 44v92H46z"
                    fill="#3C4F43"
                />

                {!compact && (
                    <>
                        <g fill="none" stroke="#9FB29B" strokeWidth="9">
                            {RING_X.map((x) => (
                                <rect
                                    key={x}
                                    x={x}
                                    y="64"
                                    width="20"
                                    height="46"
                                    rx="10"
                                />
                            ))}
                        </g>
                        <g fill="#FBF7EC" stroke="#3C4F43" strokeWidth="3">
                            {RING_X.map((x) => (
                                <rect
                                    key={x}
                                    x={x}
                                    y="64"
                                    width="20"
                                    height="30"
                                    rx="10"
                                />
                            ))}
                        </g>
                    </>
                )}

                <text
                    x="256"
                    y="178"
                    fill="#E7EFE2"
                    fontFamily={SANS_FONT}
                    fontSize="50"
                    fontWeight="650"
                    letterSpacing="11"
                    textAnchor="middle"
                >
                    ALMANAC
                </text>

                {!compact && (
                    <text
                        x="256"
                        y="288"
                        fill="#7C8A6E"
                        fontFamily={SANS_FONT}
                        fontSize="36"
                        fontWeight="600"
                        letterSpacing="9"
                        textAnchor="middle"
                    >
                        {month}
                    </text>
                )}

                <text
                    x="256"
                    y={compact ? 412 : 424}
                    fill="#26241B"
                    fontFamily={SERIF_FONT}
                    fontSize={compact ? 196 : 168}
                    fontWeight="500"
                    textAnchor="middle"
                >
                    {day}
                </text>

                <text
                    x="256"
                    y={compact ? 462 : 466}
                    fill="#4F6043"
                    fontFamily={SANS_FONT}
                    fontSize={compact ? 44 : 36}
                    fontWeight="650"
                    letterSpacing="7"
                    textAnchor="middle"
                >
                    {weekday}
                </text>
            </g>
        </svg>
    );
}
