export type MoonPhase = { name: string; glyph: string };

const SYNODIC_DAYS = 29.530588853;
const KNOWN_NEW_MOON_UTC = Date.UTC(2000, 0, 6, 18, 14, 0);

const PHASES: MoonPhase[] = [
    { name: 'New Moon', glyph: '🌑' },
    { name: 'Waxing Crescent', glyph: '🌒' },
    { name: 'First Quarter', glyph: '🌓' },
    { name: 'Waxing Gibbous', glyph: '🌔' },
    { name: 'Full Moon', glyph: '🌕' },
    { name: 'Waning Gibbous', glyph: '🌖' },
    { name: 'Last Quarter', glyph: '🌗' },
    { name: 'Waning Crescent', glyph: '🌘' },
];

export function moonPhase(date: Date = new Date()): MoonPhase {
    const days = (date.getTime() - KNOWN_NEW_MOON_UTC) / 86_400_000;
    const age = ((days % SYNODIC_DAYS) + SYNODIC_DAYS) % SYNODIC_DAYS;
    const index = Math.round(age / (SYNODIC_DAYS / 8)) % 8;
    return PHASES[index];
}
