import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    destroy as destroyCalendar,
    store as storeCalendar,
    update as updateCalendar,
} from '@/wayfinder/routes/calendar/calendars';
import {
    store as storeEvent,
    update as updateEvent,
} from '@/wayfinder/routes/calendar/events';
import type { App } from '@/wayfinder/types';
import type { Calendar, CalendarEvent } from '@/types/calendar';

function toDateTimeLocal(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

const optionalCalendarComponents = [
    { value: 'VTODO', label: 'Tasks' },
    { value: 'VJOURNAL', label: 'Notes' },
] as const;

type CreateCalendarFormData = {
    display_name: App.Http.Controllers.Calendar.CalendarManagementController.Store.Request['display_name'];
    description: string;
    color: string;
    timezone: string;
    components: string[];
};

export function CreateCalendarDialog({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<CreateCalendarFormData>({
        display_name: '',
        description: '',
        color: '',
        timezone: '',
        components: ['VEVENT'],
    });

    const toggleComponent = (value: string, checked: boolean) => {
        form.setData(
            'components',
            checked
                ? [...form.data.components, value]
                : form.data.components.filter((c) => c !== value),
        );
    };

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(storeCalendar(), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['calendars'] });
            },
        });
    }

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New calendar</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="create-calendar-name">
                            Calendar name
                        </Label>
                        <Input
                            id="create-calendar-name"
                            name="display_name"
                            value={form.data.display_name}
                            onChange={(e) =>
                                form.setData('display_name', e.target.value)
                            }
                            autoFocus
                        />
                        <InputError message={form.errors.display_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="create-calendar-description">
                            Description
                        </Label>
                        <Input
                            id="create-calendar-description"
                            name="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="create-calendar-color">Color</Label>
                            <Input
                                id="create-calendar-color"
                                name="color"
                                value={form.data.color}
                                onChange={(e) =>
                                    form.setData('color', e.target.value)
                                }
                                placeholder="#4F6043"
                            />
                            <InputError message={form.errors.color} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="create-calendar-timezone">
                                Timezone
                            </Label>
                            <Input
                                id="create-calendar-timezone"
                                name="timezone"
                                value={form.data.timezone}
                                onChange={(e) =>
                                    form.setData('timezone', e.target.value)
                                }
                                placeholder="Europe/Berlin"
                            />
                            <InputError message={form.errors.timezone} />
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        {optionalCalendarComponents.map((component) => (
                            <label
                                key={component.value}
                                className="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    aria-label={component.label}
                                    checked={form.data.components.includes(
                                        component.value,
                                    )}
                                    onChange={(e) =>
                                        toggleComponent(
                                            component.value,
                                            e.target.checked,
                                        )
                                    }
                                />
                                {component.label}
                            </label>
                        ))}
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Create calendar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

type EditCalendarFormData = {
    display_name: App.Http.Controllers.Calendar.CalendarManagementController.Update.Request['display_name'];
    description: string;
    color: string;
    timezone: string;
    components: string[];
};

export function EditCalendarDialog({
    calendar,
    open,
    onClose,
}: {
    calendar: Calendar;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<EditCalendarFormData>({
        display_name: calendar.display_name,
        description: calendar.description ?? '',
        color: calendar.color ?? '',
        timezone: calendar.timezone ?? '',
        components: calendar.components,
    });

    const toggleComponent = (value: string, checked: boolean) => {
        form.setData(
            'components',
            checked
                ? [...form.data.components, value]
                : form.data.components.filter((c) => c !== value),
        );
    };

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(updateCalendar(calendar.id), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['calendars'] });
            },
        });
    }

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit calendar</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="edit-calendar-display-name">
                            Calendar name
                        </Label>
                        <Input
                            id="edit-calendar-display-name"
                            name="edit_display_name"
                            value={form.data.display_name}
                            onChange={(e) =>
                                form.setData('display_name', e.target.value)
                            }
                            autoFocus
                        />
                        <InputError message={form.errors.display_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-calendar-description">
                            Description
                        </Label>
                        <Input
                            id="edit-calendar-description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-calendar-color">Color</Label>
                            <Input
                                id="edit-calendar-color"
                                value={form.data.color}
                                onChange={(e) =>
                                    form.setData('color', e.target.value)
                                }
                                placeholder="#4F6043"
                            />
                            <InputError message={form.errors.color} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-calendar-timezone">
                                Timezone
                            </Label>
                            <Input
                                id="edit-calendar-timezone"
                                value={form.data.timezone}
                                onChange={(e) =>
                                    form.setData('timezone', e.target.value)
                                }
                                placeholder="Europe/Berlin"
                            />
                            <InputError message={form.errors.timezone} />
                        </div>
                    </div>

                    {calendar.is_owner && (
                        <div className="flex flex-col gap-2">
                            {optionalCalendarComponents.map((component) => (
                                <label
                                    key={component.value}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        aria-label={component.label}
                                        checked={form.data.components.includes(
                                            component.value,
                                        )}
                                        onChange={(e) =>
                                            toggleComponent(
                                                component.value,
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    {component.label}
                                </label>
                            ))}
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save calendar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function DeleteCalendarDialog({
    calendar,
    open,
    onClose,
}: {
    calendar: Calendar;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm({});

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(destroyCalendar(calendar.id), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['calendars'] });
            },
        });
    }

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {calendar.is_owner
                            ? 'Delete calendar?'
                            : 'Remove calendar?'}
                    </DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    {calendar.is_owner ? (
                        <>
                            This will permanently remove{' '}
                            <span className="font-medium text-foreground">
                                {calendar.display_name}
                            </span>{' '}
                            and its events.
                        </>
                    ) : (
                        <>
                            This will remove{' '}
                            <span className="font-medium text-foreground">
                                {calendar.display_name}
                            </span>{' '}
                            from your calendar list.
                        </>
                    )}
                </p>

                <form onSubmit={submit} className="grid gap-4">
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={form.processing}
                        >
                            {calendar.is_owner
                                ? 'Delete calendar'
                                : 'Remove calendar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

type CreateEventFormData = {
    calendar_id: string;
    summary: string;
    description: string;
    location: string;
    starts_at: string;
    ends_at: string;
    is_all_day: boolean;
};

export type CreateEventDefaults = {
    start: Date;
    end?: Date;
    isAllDay?: boolean;
};

export function CreateEventDialog({
    calendars,
    open,
    onClose,
    defaults,
}: {
    calendars: Calendar[];
    open: boolean;
    onClose: () => void;
    defaults?: CreateEventDefaults;
}) {
    const start = defaults?.start ?? new Date();
    const end =
        defaults?.end && defaults.end.getTime() > start.getTime()
            ? defaults.end
            : new Date(start.getTime() + 60 * 60 * 1000);

    const form = useForm<CreateEventFormData>({
        calendar_id: calendars[0] ? String(calendars[0].dav_calendar_id) : '',
        summary: '',
        description: '',
        location: '',
        starts_at: toDateTimeLocal(start),
        ends_at: toDateTimeLocal(end),
        is_all_day: defaults?.isAllDay ?? false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            calendar_id: data.calendar_id,
            data: {
                summary: data.summary,
                description: data.description,
                location: data.location,
                status: '',
                url: '',
                startsAt: data.starts_at,
                endsAt: data.ends_at,
                isAllDay: data.is_all_day,
            },
        }));
        form.submit(storeEvent(), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['calendars'] });
            },
        });
    }

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New event</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="create-event-calendar">Calendar</Label>
                        <Select
                            value={form.data.calendar_id}
                            onValueChange={(value) =>
                                form.setData('calendar_id', value)
                            }
                        >
                            <SelectTrigger
                                id="create-event-calendar"
                                className="w-full"
                            >
                                <SelectValue placeholder="Select a calendar" />
                            </SelectTrigger>
                            <SelectContent>
                                {calendars.map((cal) => (
                                    <SelectItem
                                        key={cal.id}
                                        value={String(cal.dav_calendar_id)}
                                    >
                                        {cal.display_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.calendar_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="summary">Summary</Label>
                        <Input
                            id="summary"
                            name="summary"
                            value={form.data.summary}
                            onChange={(e) =>
                                form.setData('summary', e.target.value)
                            }
                            placeholder="Event title"
                            autoFocus
                        />
                        <InputError message={form.errors.summary} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="create-event-all-day"
                            checked={form.data.is_all_day}
                            onCheckedChange={(checked) =>
                                form.setData('is_all_day', checked === true)
                            }
                        />
                        <Label htmlFor="create-event-all-day">All day</Label>
                    </div>

                    {!form.data.is_all_day && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="starts_at">Starts at</Label>
                                <Input
                                    id="starts_at"
                                    name="starts_at"
                                    type="datetime-local"
                                    value={form.data.starts_at}
                                    onChange={(e) =>
                                        form.setData(
                                            'starts_at',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.starts_at} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="ends_at">Ends at</Label>
                                <Input
                                    id="ends_at"
                                    name="ends_at"
                                    type="datetime-local"
                                    value={form.data.ends_at}
                                    onChange={(e) =>
                                        form.setData('ends_at', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.ends_at} />
                            </div>
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="create-event-location">Location</Label>
                        <Input
                            id="create-event-location"
                            value={form.data.location}
                            onChange={(e) =>
                                form.setData('location', e.target.value)
                            }
                            placeholder="Location"
                        />
                        <InputError message={form.errors.location} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="create-event-description">
                            Description
                        </Label>
                        <Textarea
                            id="create-event-description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            placeholder="Description"
                            rows={3}
                            className="border-input bg-card/70 placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/35 flex min-h-0 w-full rounded-md border px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Create event
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

type EditEventFormData = {
    summary: string;
    description: string;
    location: string;
    starts_at: string;
    ends_at: string;
    is_all_day: boolean;
    status: string;
    url: string;
    expected_etag: string;
    conflict?: string;
};

export function EditEventDialog({
    event,
    open,
    onClose,
}: {
    event: CalendarEvent;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<EditEventFormData>({
        summary: event.data.summary ?? '',
        description: event.data.description ?? '',
        location: event.data.location ?? '',
        starts_at: event.starts_at?.slice(0, 16) ?? '',
        ends_at: event.ends_at?.slice(0, 16) ?? '',
        is_all_day: event.is_all_day,
        status: event.data.status ?? '',
        url: event.data.url ?? '',
        expected_etag: event.etag,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            expected_etag: data.expected_etag,
            data: {
                summary: data.summary,
                description: data.description,
                location: data.location,
                status: data.status,
                url: data.url,
                startsAt: data.starts_at,
                endsAt: data.ends_at,
                isAllDay: data.is_all_day,
            },
        }));
        form.submit(updateEvent(event.id), {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.conflict) {
                    router.reload({ only: ['calendars'] });
                }
            },
            onSuccess: () => {
                onClose();
            },
        });
    }

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit event</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="edit-event-summary">Summary</Label>
                        <Input
                            id="edit-event-summary"
                            value={form.data.summary}
                            onChange={(e) =>
                                form.setData('summary', e.target.value)
                            }
                            placeholder="Event title"
                            autoFocus
                        />
                        <InputError message={form.errors.summary} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="edit-event-all-day"
                            checked={form.data.is_all_day}
                            onCheckedChange={(checked) =>
                                form.setData('is_all_day', checked === true)
                            }
                        />
                        <Label htmlFor="edit-event-all-day">All day</Label>
                    </div>

                    {!form.data.is_all_day && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-event-starts-at">
                                    Starts at
                                </Label>
                                <Input
                                    id="edit-event-starts-at"
                                    type="datetime-local"
                                    value={form.data.starts_at}
                                    onChange={(e) =>
                                        form.setData(
                                            'starts_at',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.starts_at} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-event-ends-at">
                                    Ends at
                                </Label>
                                <Input
                                    id="edit-event-ends-at"
                                    type="datetime-local"
                                    value={form.data.ends_at}
                                    onChange={(e) =>
                                        form.setData('ends_at', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.ends_at} />
                            </div>
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="edit-event-status">Status</Label>
                        <Select
                            value={form.data.status}
                            onValueChange={(value) =>
                                form.setData(
                                    'status',
                                    value === 'NONE' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger
                                id="edit-event-status"
                                className="w-full"
                            >
                                <SelectValue placeholder="No status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="NONE">No status</SelectItem>
                                <SelectItem value="CONFIRMED">
                                    Confirmed
                                </SelectItem>
                                <SelectItem value="TENTATIVE">
                                    Tentative
                                </SelectItem>
                                <SelectItem value="CANCELLED">
                                    Cancelled
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.status} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-event-url">URL</Label>
                        <Input
                            id="edit-event-url"
                            type="url"
                            value={form.data.url}
                            onChange={(e) =>
                                form.setData('url', e.target.value)
                            }
                            placeholder="https://example.com"
                        />
                        <InputError message={form.errors.url} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-event-location">Location</Label>
                        <Input
                            id="edit-event-location"
                            value={form.data.location}
                            onChange={(e) =>
                                form.setData('location', e.target.value)
                            }
                            placeholder="Location"
                        />
                        <InputError message={form.errors.location} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-event-description">
                            Description
                        </Label>
                        <Textarea
                            id="edit-event-description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            placeholder="Description"
                            rows={3}
                            className="border-input bg-card/70 placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/35 flex min-h-0 w-full rounded-md border px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    {form.errors.conflict && (
                        <p className="text-sm text-destructive">
                            {form.errors.conflict}
                        </p>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
