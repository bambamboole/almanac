export type ContactAddressBook = {
    id: number;
    name: string;
    description: string | null;
    contacts_count: number;
};

export type Contact = {
    id: number;
    address_book_id: number;
    address_book: {
        id: number;
        name: string;
    };
    display_name: string;
    full_name: string | null;
    given_name: string | null;
    family_name: string | null;
    organization: string | null;
    job_title: string | null;
    nickname: string | null;
    note: string | null;
    etag: string;
    emails: string[];
    phones: string[];
    email_addresses: ContactEmailAddress[];
    phone_numbers: ContactPhoneNumber[];
    addresses: ContactPostalAddress[];
    primary_email: string | null;
    primary_phone: string | null;
    updated_at: string | null;
};

export type ContactEmailAddress = {
    label: string | null;
    value: string;
    types: string[];
    is_preferred: boolean;
    group: string | null;
};

export type ContactPhoneNumber = {
    label: string | null;
    value: string;
    types: string[];
    is_preferred: boolean;
    group: string | null;
};

export type ContactPostalAddress = {
    label: string | null;
    po_box: string | null;
    extended: string | null;
    street: string | null;
    city: string | null;
    region: string | null;
    postal_code: string | null;
    country: string | null;
    country_code: string | null;
    types: string[];
    is_preferred: boolean;
    group: string | null;
};
