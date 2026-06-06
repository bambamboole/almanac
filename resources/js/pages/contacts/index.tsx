import { Head, router, usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import '@/lib/echo';
import {
    Building2,
    ContactRound,
    Download,
    Mail,
    MoreHorizontal,
    Pencil,
    Phone,
    Plus,
    Search,
    Trash2,
    UsersRound,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    CreateAddressBookDialog,
    CreateContactDialog,
    DeleteAddressBookDialog,
    DeleteContactDialog,
    EditAddressBookDialog,
    EditContactDialog,
} from '@/components/contacts/contact-dialogs';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { exportMethod as exportContacts } from '@/wayfinder/routes/contacts';
import { exportMethod as exportSingleAddressBook } from '@/wayfinder/routes/contacts/address-books';
import { exportMethod as exportContact } from '@/wayfinder/routes/contacts/cards';
import {
    contactDisplayName,
    contactPrimaryEmail,
    contactPrimaryPhone,
} from '@/components/contacts/contact-form';
import type { Contact, ContactAddressBook } from '@/types/contacts';

type Props = {
    addressBooks: ContactAddressBook[];
    contacts: Contact[];
};

type DavChangedPayload = {
    type: 'calendar' | 'address_book';
    collection_id: number;
    uri: string;
    operation: string;
    sync_token: string;
};

function initialsFor(contact: Contact): string {
    const name = contactDisplayName(contact).trim();
    const parts = name.split(/\s+/).filter(Boolean);

    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
    }

    return name.slice(0, 2).toUpperCase() || '??';
}

function contactMatchesQuery(contact: Contact, query: string): boolean {
    const data = contact.data;
    const haystack = [
        contactDisplayName(contact),
        data.organization,
        contactPrimaryEmail(contact),
        contactPrimaryPhone(contact),
        contact.address_book.display_name,
        ...data.emailAddresses.map((email) => email.value),
        ...data.phoneNumbers.map((phone) => phone.value),
        ...data.addresses.flatMap((address) => [
            address.street,
            address.city,
            address.region,
            address.postalCode,
            address.country,
        ]),
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

    return haystack.includes(query);
}

export default function ContactsIndex({ addressBooks, contacts }: Props) {
    const userId = usePage().props.auth.user.id;
    const [selectedAddressBookId, setSelectedAddressBookId] = useState<
        number | null
    >(null);
    const [query, setQuery] = useState('');
    const [creatingContact, setCreatingContact] = useState(false);
    const [creatingAddressBook, setCreatingAddressBook] = useState(false);
    const [editingAddressBook, setEditingAddressBook] =
        useState<ContactAddressBook | null>(null);
    const [deletingAddressBook, setDeletingAddressBook] =
        useState<ContactAddressBook | null>(null);
    const [editingContact, setEditingContact] = useState<Contact | null>(null);
    const [deletingContact, setDeletingContact] = useState<Contact | null>(
        null,
    );

    useEcho<DavChangedPayload>(
        `dav.${userId}`,
        '.dav.changed',
        (e) => {
            if (e.type === 'address_book') {
                router.reload({ only: ['addressBooks', 'contacts'] });
            }
        },
        [userId],
        'private',
    );

    const normalizedQuery = query.trim().toLowerCase();

    const filteredContacts = useMemo(() => {
        return contacts.filter((contact) => {
            const matchesAddressBook =
                selectedAddressBookId === null ||
                contact.address_book.id === selectedAddressBookId;
            const matchesQuery =
                normalizedQuery.length === 0 ||
                contactMatchesQuery(contact, normalizedQuery);

            return matchesAddressBook && matchesQuery;
        });
    }, [contacts, normalizedQuery, selectedAddressBookId]);

    const selectedAddressBook = addressBooks.find(
        (addressBook) => addressBook.id === selectedAddressBookId,
    );
    const resultLabel =
        filteredContacts.length === 1
            ? '1 contact'
            : `${filteredContacts.length} contacts`;

    return (
        <>
            <Head title="Contacts" />

            {creatingContact && (
                <CreateContactDialog
                    addressBooks={addressBooks}
                    open={creatingContact}
                    onClose={() => setCreatingContact(false)}
                />
            )}

            {creatingAddressBook && (
                <CreateAddressBookDialog
                    open={creatingAddressBook}
                    onClose={() => setCreatingAddressBook(false)}
                />
            )}

            {editingContact && (
                <EditContactDialog
                    contact={editingContact}
                    open={editingContact !== null}
                    onClose={() => setEditingContact(null)}
                />
            )}

            {editingAddressBook && (
                <EditAddressBookDialog
                    key={editingAddressBook.id}
                    addressBook={editingAddressBook}
                    open={editingAddressBook !== null}
                    onClose={() => setEditingAddressBook(null)}
                />
            )}

            {deletingAddressBook && (
                <DeleteAddressBookDialog
                    addressBook={deletingAddressBook}
                    open={deletingAddressBook !== null}
                    onClose={() => setDeletingAddressBook(null)}
                    onDeleted={() => setSelectedAddressBookId(null)}
                />
            )}

            {deletingContact && (
                <DeleteContactDialog
                    contact={deletingContact}
                    open={deletingContact !== null}
                    onClose={() => setDeletingContact(null)}
                />
            )}

            <div className="mx-auto flex h-full min-h-0 w-full max-w-[88rem] flex-1 flex-col gap-5 px-5 pt-6 md:px-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Contacts"
                        description="Organize your people and address books."
                    />

                    <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                        <div className="relative w-full sm:w-80">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                placeholder="Search contacts"
                                className="pl-9"
                            />
                        </div>

                        <Button
                            className="shrink-0"
                            onClick={() => setCreatingContact(true)}
                        >
                            <Plus className="size-4" />
                            New contact
                        </Button>
                    </div>
                </div>

                <div className="grid min-h-0 gap-4 lg:grid-cols-[18rem_minmax(0,1fr)]">
                    <aside className="almanac-panel min-h-0">
                        <div className="flex items-center justify-between gap-2 border-b border-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">
                                Address Books
                            </h2>
                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-7"
                                    aria-label="New address book"
                                    data-new-address-book
                                    onClick={() => setCreatingAddressBook(true)}
                                >
                                    <Plus className="size-3.5" />
                                </Button>
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="icon"
                                    className="size-7"
                                >
                                    <a
                                        href={exportContacts.url()}
                                        download
                                        aria-label="Export contacts"
                                        data-export-contacts
                                    >
                                        <Download className="size-3.5" />
                                    </a>
                                </Button>
                            </div>
                        </div>
                        <div className="overflow-y-auto p-2">
                            <Button
                                type="button"
                                variant={
                                    selectedAddressBookId === null
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                className="h-auto w-full justify-between gap-3 px-3 py-2"
                                onClick={() => setSelectedAddressBookId(null)}
                            >
                                <span className="flex min-w-0 items-center gap-2">
                                    <UsersRound className="size-4 shrink-0" />
                                    <span className="truncate">
                                        All contacts
                                    </span>
                                </span>
                                <Badge variant="outline">
                                    {contacts.length}
                                </Badge>
                            </Button>

                            {addressBooks.map((addressBook) => (
                                <div
                                    key={addressBook.id}
                                    className="mt-1 flex min-w-0 items-start gap-1"
                                >
                                    <Button
                                        type="button"
                                        variant={
                                            selectedAddressBookId ===
                                            addressBook.id
                                                ? 'secondary'
                                                : 'ghost'
                                        }
                                        className="h-auto min-w-0 flex-1 justify-between gap-3 px-3 py-2"
                                        onClick={() =>
                                            setSelectedAddressBookId(
                                                addressBook.id,
                                            )
                                        }
                                    >
                                        <span className="flex min-w-0 items-center gap-2">
                                            <ContactRound className="size-4 shrink-0" />
                                            <span className="min-w-0 text-left">
                                                <span className="block truncate">
                                                    {addressBook.display_name}
                                                </span>
                                                {addressBook.description && (
                                                    <span className="mt-0.5 block truncate text-xs font-normal text-muted-foreground">
                                                        {
                                                            addressBook.description
                                                        }
                                                    </span>
                                                )}
                                            </span>
                                        </span>
                                        <Badge variant="outline">
                                            {addressBook.cards_count}
                                        </Badge>
                                    </Button>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="mt-1 size-7 shrink-0"
                                                aria-label={`${addressBook.display_name} actions`}
                                                data-address-book-actions={
                                                    addressBook.id
                                                }
                                            >
                                                <MoreHorizontal className="size-3.5" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                data-edit-address-book={
                                                    addressBook.id
                                                }
                                                onSelect={() =>
                                                    setEditingAddressBook(
                                                        addressBook,
                                                    )
                                                }
                                            >
                                                <Pencil className="size-4" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <a
                                                    href={exportSingleAddressBook.url(
                                                        addressBook.id,
                                                    )}
                                                    download
                                                    data-export-address-book={
                                                        addressBook.id
                                                    }
                                                >
                                                    <Download className="size-4" />
                                                    Export
                                                </a>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                variant="destructive"
                                                data-delete-address-book={
                                                    addressBook.id
                                                }
                                                onSelect={() =>
                                                    setDeletingAddressBook(
                                                        addressBook,
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            ))}
                        </div>
                    </aside>

                    <main className="almanac-panel min-h-0">
                        <div className="flex flex-col gap-1 border-b border-border/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="min-w-0">
                                <h2 className="truncate text-sm font-semibold">
                                    {selectedAddressBook?.display_name ??
                                        'All contacts'}
                                </h2>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    {resultLabel}
                                </p>
                            </div>
                            {normalizedQuery && (
                                <Badge variant="outline">
                                    Search: {query.trim()}
                                </Badge>
                            )}
                        </div>

                        {contacts.length === 0 ? (
                            <div className="flex min-h-80 flex-col items-center justify-center p-8 text-center">
                                <div className="mb-4 flex size-12 items-center justify-center rounded-md bg-secondary text-secondary-foreground">
                                    <ContactRound className="size-6 text-muted-foreground" />
                                </div>
                                <p className="font-medium">No contacts</p>
                                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                    Contacts from connected DAV address books
                                    will appear here.
                                </p>
                            </div>
                        ) : filteredContacts.length === 0 ? (
                            <div className="flex min-h-80 flex-col items-center justify-center p-8 text-center">
                                <div className="mb-4 flex size-12 items-center justify-center rounded-md bg-secondary text-secondary-foreground">
                                    <Search className="size-6 text-muted-foreground" />
                                </div>
                                <p className="font-medium">No matches</p>
                                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                    Adjust the active address book or search
                                    term.
                                </p>
                            </div>
                        ) : (
                            <div className="max-h-full divide-y divide-border overflow-y-auto">
                                {filteredContacts.map((contact) => (
                                    <article
                                        key={contact.id}
                                        data-contact-row={contact.id}
                                        className="relative"
                                    >
                                        <button
                                            type="button"
                                            className="grid w-full min-w-0 cursor-pointer gap-3 p-4 pr-14 text-left transition hover:bg-accent/45 focus-visible:bg-accent/45 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring/40 sm:grid-cols-[3rem_minmax(0,1fr)]"
                                            onClick={() =>
                                                setEditingContact(contact)
                                            }
                                        >
                                            <div className="flex size-11 items-center justify-center rounded-md bg-secondary text-sm font-semibold text-secondary-foreground">
                                                {initialsFor(contact)}
                                            </div>

                                            <div className="min-w-0">
                                                <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                    <div className="min-w-0">
                                                        <h3 className="min-w-0 text-sm font-medium break-words">
                                                            {contactDisplayName(
                                                                contact,
                                                            )}
                                                        </h3>
                                                        <p className="mt-1 truncate text-xs text-muted-foreground">
                                                            {
                                                                contact
                                                                    .address_book
                                                                    .display_name
                                                            }
                                                        </p>
                                                    </div>
                                                    {contact.data
                                                        .organization && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="w-fit max-w-full justify-start gap-1.5"
                                                        >
                                                            <Building2 className="size-3" />
                                                            <span className="truncate">
                                                                {
                                                                    contact.data
                                                                        .organization
                                                                }
                                                            </span>
                                                        </Badge>
                                                    )}
                                                </div>

                                                <div className="mt-3 grid min-w-0 gap-2 text-sm text-muted-foreground md:grid-cols-2">
                                                    {contactPrimaryEmail(
                                                        contact,
                                                    ) && (
                                                        <p className="flex min-w-0 items-center gap-2">
                                                            <Mail className="size-4 shrink-0" />
                                                            <span className="min-w-0 truncate">
                                                                {contactPrimaryEmail(
                                                                    contact,
                                                                )}
                                                            </span>
                                                        </p>
                                                    )}
                                                    {contactPrimaryPhone(
                                                        contact,
                                                    ) && (
                                                        <p className="flex min-w-0 items-center gap-2">
                                                            <Phone className="size-4 shrink-0" />
                                                            <span className="min-w-0 truncate">
                                                                {contactPrimaryPhone(
                                                                    contact,
                                                                )}
                                                            </span>
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </button>

                                        <div className="absolute top-4 right-4">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7 shrink-0"
                                                        aria-label={`${contactDisplayName(contact)} actions`}
                                                        data-contact-actions={
                                                            contact.id
                                                        }
                                                    >
                                                        <MoreHorizontal className="size-3.5" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <a
                                                            href={exportContact.url(
                                                                contact.id,
                                                            )}
                                                            download
                                                            data-export-contact={
                                                                contact.id
                                                            }
                                                        >
                                                            <Download className="size-4" />
                                                            Download vCard
                                                        </a>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        data-delete-contact={
                                                            contact.id
                                                        }
                                                        onSelect={() =>
                                                            setDeletingContact(
                                                                contact,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                        Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </main>
                </div>
            </div>
        </>
    );
}
