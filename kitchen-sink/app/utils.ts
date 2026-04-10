export function formatTime(date: string) {
	return new Intl.DateTimeFormat(undefined, { timeStyle: 'medium' }).format(new Date(date))
}
