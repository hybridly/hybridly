import { Temporal } from 'temporal-polyfill'

/**
 * Whether to show date times in local timezone or original timezone.
 */
export const showLocalDateTimes = useLocalStorage('show-local-dates', true)

/**
 * Toggles the display mode between local timezone and original timezone.
 */
export function toggleLocalDateTimes(): void {
	showLocalDateTimes.value = !showLocalDateTimes.value
}

export interface FormatDateTimeOptions extends Intl.DateTimeFormatOptions {
	local?: boolean
	locale?: string
	relative?: boolean
	withoutDate?: true
	withoutTime?: true
}

/**
 * Gets the local timezone offset in the format ±HH:MM.
 */
export function getLocalOffset() {
	return Temporal.Now.zonedDateTimeISO().offset
}

/**
 * Formats a relative time string (e.g., "2 hours ago", "in 3 days").
 */
function formatRelativeTime(instant: Temporal.Instant, locale: string, local: boolean): string {
	const now = Temporal.Now.instant()
	const duration = now.since(instant)
	const formatter = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })

	const seconds = duration.total('seconds')
	if (Math.abs(seconds) < 60) {
		return formatter.format(-Math.round(seconds), 'second')
	}

	const minutes = duration.total('minutes')
	if (Math.abs(minutes) < 60) {
		return formatter.format(-Math.round(minutes), 'minute')
	}

	const hours = duration.total('hours')
	if (Math.abs(hours) < 24) {
		return formatter.format(-Math.round(hours), 'hour')
	}

	const days = duration.total('days')
	if (Math.abs(days) < 30) {
		return formatter.format(-Math.round(days), 'day')
	}

	const timeZone = local ? Temporal.Now.zonedDateTimeISO().timeZoneId : 'UTC'
	const zonedNow = now.toZonedDateTimeISO(timeZone)
	const zonedThen = instant.toZonedDateTimeISO(timeZone)
	const zonedDuration = zonedNow.since(zonedThen)

	const months = zonedDuration.total({ unit: 'months', relativeTo: zonedThen })
	if (Math.abs(months) < 12) {
		return formatter.format(-Math.round(months), 'month')
	}

	const years = zonedDuration.total({ unit: 'years', relativeTo: zonedThen })
	return formatter.format(-Math.round(years), 'year')
}

/**
 * Formats a RFC 9557 datetime string.
 */
export function formatDateTime(datetime: string, options: FormatDateTimeOptions = {}): string {
	const {
		local = showLocalDateTimes.value,
		locale = 'en-GB',
		relative = false,
		...intlOptions
	} = options

	const instant = Temporal.Instant.from(datetime)

	if (relative) {
		return formatRelativeTime(instant, locale, local)
	}

	const converted = local
		? instant.toZonedDateTimeISO(Temporal.Now.zonedDateTimeISO().timeZoneId)
		: instant.toZonedDateTimeISO('UTC')

	const defaultOptions: FormatDateTimeOptions = {
		dateStyle: !intlOptions.month && !intlOptions.day && intlOptions.withoutDate !== true ? 'medium' : undefined,
		timeStyle: !intlOptions.month && !intlOptions.day && intlOptions.withoutTime !== true ? 'short' : undefined,
	}

	return converted.toLocaleString(locale, { ...defaultOptions, ...intlOptions })
}
