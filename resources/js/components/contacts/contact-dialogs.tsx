import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
    destroy as destroyContact,
    store as storeContact,
    update as updateContact,
} from '@/wayfinder/routes/contacts';
import {
    destroy as destroyAddressBook,
    store as storeAddressBook,
    update as updateAddressBook,
} from '@/wayfinder/routes/contacts/address-books';
import type { App } from '@/wayfinder/types';
import type { Contact, ContactAddressBook } from '@/types/contacts';
import {
    blankEmailAddress,
    blankPhoneNumber,
    blankPostalAddress,
    ContactStructuredFields,
    emailAddressFromContact,
    phoneNumberFromContact,
    postalAddressFromContact,
} from './contact-form';
import type { ContactFormFields, CreateContactFormData } from './contact-form';

export function CreateContactDialog({
    addressBooks,
    open,
    onClose,
}: {
    addressBooks: ContactAddressBook[];
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<CreateContactFormData>({
        address_book_id: addressBooks[0] ? String(addressBooks[0].id) : '',
        full_name: '',
        given_name: '',
        family_name: '',
        organization: '',
        job_title: '',
        nickname: '',
        note: '',
        email_addresses: [blankEmailAddress()],
        phone_numbers: [blankPhoneNumber()],
        addresses: [blankPostalAddress()],
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(storeContact(), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['contacts'] });
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
            <DialogContent className="gap-5 p-5 sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>New contact</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-5">
                    <div className="grid gap-5 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.25fr)]">
                        <div className="grid content-start gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="create-contact-address-book">
                                    Address book
                                </Label>
                                <Select
                                    value={form.data.address_book_id}
                                    onValueChange={(value) =>
                                        form.setData('address_book_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="create-contact-address-book"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select an address book" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {addressBooks.map((addressBook) => (
                                            <SelectItem
                                                key={addressBook.id}
                                                value={String(addressBook.id)}
                                            >
                                                {addressBook.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.address_book_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="full_name">Full name</Label>
                                <Input
                                    id="full_name"
                                    name="full_name"
                                    value={form.data.full_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'full_name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Full name"
                                    autoFocus
                                />
                                <InputError message={form.errors.full_name} />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="given_name">
                                        Given name
                                    </Label>
                                    <Input
                                        id="given_name"
                                        name="given_name"
                                        value={form.data.given_name}
                                        onChange={(e) =>
                                            form.setData(
                                                'given_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Given name"
                                    />
                                    <InputError
                                        message={form.errors.given_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="family_name">
                                        Family name
                                    </Label>
                                    <Input
                                        id="family_name"
                                        name="family_name"
                                        value={form.data.family_name}
                                        onChange={(e) =>
                                            form.setData(
                                                'family_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Family name"
                                    />
                                    <InputError
                                        message={form.errors.family_name}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="organization">
                                    Organization
                                </Label>
                                <Input
                                    id="organization"
                                    name="organization"
                                    value={form.data.organization}
                                    onChange={(e) =>
                                        form.setData(
                                            'organization',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Organization"
                                />
                                <InputError
                                    message={form.errors.organization}
                                />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="job_title">Job title</Label>
                                    <Input
                                        id="job_title"
                                        name="job_title"
                                        value={form.data.job_title}
                                        onChange={(e) =>
                                            form.setData(
                                                'job_title',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Job title"
                                    />
                                    <InputError
                                        message={form.errors.job_title}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="nickname">Nickname</Label>
                                    <Input
                                        id="nickname"
                                        name="nickname"
                                        value={form.data.nickname}
                                        onChange={(e) =>
                                            form.setData(
                                                'nickname',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Nickname"
                                    />
                                    <InputError
                                        message={form.errors.nickname}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="create-contact-note">
                                    Note
                                </Label>
                                <Textarea
                                    id="create-contact-note"
                                    value={form.data.note}
                                    onChange={(e) =>
                                        form.setData('note', e.target.value)
                                    }
                                    placeholder="Note"
                                    rows={3}
                                    className="border-input bg-card/70 placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/35 flex min-h-0 w-full rounded-md border px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <InputError message={form.errors.note} />
                            </div>
                        </div>

                        <div className="grid content-start gap-4 rounded-md bg-muted/10 p-3">
                            <ContactStructuredFields
                                data={form.data}
                                errors={
                                    form.errors as Partial<
                                        Record<string, string>
                                    >
                                }
                                idPrefix="create-contact"
                                setContactField={(key, value) =>
                                    form.setData(
                                        key as keyof CreateContactFormData,
                                        value as never,
                                    )
                                }
                            />
                        </div>
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
                            Create contact
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

type CreateAddressBookFormData = {
    display_name: App.Http.Controllers.Contacts.AddressBookManagementController.Store.Request['display_name'];
    description: string;
};

export function CreateAddressBookDialog({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<CreateAddressBookFormData>({
        display_name: '',
        description: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(storeAddressBook(), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['addressBooks'] });
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
                    <DialogTitle>New address book</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="create-address-book-name">
                            Address book name
                        </Label>
                        <Input
                            id="create-address-book-name"
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
                        <Label htmlFor="create-address-book-description">
                            Description
                        </Label>
                        <Input
                            id="create-address-book-description"
                            name="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
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
                            Create address book
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

type EditAddressBookFormData = {
    display_name: App.Http.Controllers.Contacts.AddressBookManagementController.Update.Request['display_name'];
    description: string;
};

export function EditAddressBookDialog({
    addressBook,
    open,
    onClose,
}: {
    addressBook: ContactAddressBook;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<EditAddressBookFormData>({
        display_name: addressBook.name,
        description: addressBook.description ?? '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(updateAddressBook(addressBook.id), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['addressBooks', 'contacts'] });
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
                    <DialogTitle>Edit address book</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="edit-address-book-display-name">
                            Address book name
                        </Label>
                        <Input
                            id="edit-address-book-display-name"
                            name="edit_address_book_display_name"
                            value={form.data.display_name}
                            onChange={(e) =>
                                form.setData('display_name', e.target.value)
                            }
                            autoFocus
                        />
                        <InputError message={form.errors.display_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-address-book-description">
                            Description
                        </Label>
                        <Input
                            id="edit-address-book-description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
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
                            Save address book
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function DeleteAddressBookDialog({
    addressBook,
    open,
    onClose,
    onDeleted,
}: {
    addressBook: ContactAddressBook;
    open: boolean;
    onClose: () => void;
    onDeleted: () => void;
}) {
    const form = useForm({});

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(destroyAddressBook(addressBook.id), {
            preserveScroll: true,
            onSuccess: () => {
                onDeleted();
                onClose();
                router.reload({ only: ['addressBooks', 'contacts'] });
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
                    <DialogTitle>Delete address book?</DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    This will permanently remove{' '}
                    <span className="font-medium text-foreground">
                        {addressBook.name}
                    </span>{' '}
                    and its contacts.
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
                            Delete address book
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

type EditContactFormData = ContactFormFields & {
    expected_etag: App.Http.Controllers.Contacts.ContactController.Update.Request['expected_etag'];
    conflict?: string;
};

export function EditContactDialog({
    contact,
    open,
    onClose,
}: {
    contact: Contact;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<EditContactFormData>({
        full_name: contact.full_name ?? '',
        given_name: contact.given_name ?? '',
        family_name: contact.family_name ?? '',
        organization: contact.organization ?? '',
        job_title: contact.job_title ?? '',
        nickname: contact.nickname ?? '',
        note: contact.note ?? '',
        email_addresses:
            contact.email_addresses.length > 0
                ? contact.email_addresses.map(emailAddressFromContact)
                : [blankEmailAddress()],
        phone_numbers:
            contact.phone_numbers.length > 0
                ? contact.phone_numbers.map(phoneNumberFromContact)
                : [blankPhoneNumber()],
        addresses:
            contact.addresses.length > 0
                ? contact.addresses.map(postalAddressFromContact)
                : [blankPostalAddress()],
        expected_etag: contact.etag,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(updateContact(contact.id), {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.conflict) {
                    router.reload({ only: ['contacts'] });
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
            <DialogContent className="gap-5 p-5 sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>Edit contact</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-5">
                    <div className="grid gap-5 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.25fr)]">
                        <div className="grid content-start gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-contact-full-name">
                                    Full name
                                </Label>
                                <Input
                                    id="edit-contact-full-name"
                                    value={form.data.full_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'full_name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Full name"
                                    autoFocus
                                />
                                <InputError message={form.errors.full_name} />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-contact-given-name">
                                        Given name
                                    </Label>
                                    <Input
                                        id="edit-contact-given-name"
                                        value={form.data.given_name}
                                        onChange={(e) =>
                                            form.setData(
                                                'given_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Given name"
                                    />
                                    <InputError
                                        message={form.errors.given_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="edit-contact-family-name">
                                        Family name
                                    </Label>
                                    <Input
                                        id="edit-contact-family-name"
                                        value={form.data.family_name}
                                        onChange={(e) =>
                                            form.setData(
                                                'family_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Family name"
                                    />
                                    <InputError
                                        message={form.errors.family_name}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-contact-organization">
                                    Organization
                                </Label>
                                <Input
                                    id="edit-contact-organization"
                                    value={form.data.organization}
                                    onChange={(e) =>
                                        form.setData(
                                            'organization',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Organization"
                                />
                                <InputError
                                    message={form.errors.organization}
                                />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-contact-job-title">
                                        Job title
                                    </Label>
                                    <Input
                                        id="edit-contact-job-title"
                                        value={form.data.job_title}
                                        onChange={(e) =>
                                            form.setData(
                                                'job_title',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Job title"
                                    />
                                    <InputError
                                        message={form.errors.job_title}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="edit-contact-nickname">
                                        Nickname
                                    </Label>
                                    <Input
                                        id="edit-contact-nickname"
                                        value={form.data.nickname}
                                        onChange={(e) =>
                                            form.setData(
                                                'nickname',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Nickname"
                                    />
                                    <InputError
                                        message={form.errors.nickname}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-contact-note">Note</Label>
                                <Textarea
                                    id="edit-contact-note"
                                    value={form.data.note}
                                    onChange={(e) =>
                                        form.setData('note', e.target.value)
                                    }
                                    placeholder="Note"
                                    rows={3}
                                    className="border-input bg-card/70 placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/35 flex min-h-0 w-full rounded-md border px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <InputError message={form.errors.note} />
                            </div>
                        </div>

                        <div className="grid content-start gap-4 rounded-md bg-muted/10 p-3">
                            <ContactStructuredFields
                                data={form.data}
                                errors={
                                    form.errors as Partial<
                                        Record<string, string>
                                    >
                                }
                                idPrefix="edit-contact"
                                setContactField={(key, value) =>
                                    form.setData(
                                        key as keyof EditContactFormData,
                                        value as never,
                                    )
                                }
                            />
                        </div>
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

export function DeleteContactDialog({
    contact,
    open,
    onClose,
}: {
    contact: Contact;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm({
        expected_etag: contact.etag,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.submit(destroyContact(contact.id), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['contacts'] });
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
                    <DialogTitle>Delete contact?</DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    This will permanently remove{' '}
                    <span className="font-medium text-foreground">
                        {contact.display_name}
                    </span>{' '}
                    from your address book.
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
                            Delete contact
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
