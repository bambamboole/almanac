import { Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    Contact,
    ContactLabeledValue,
    ContactPostalAddress,
} from '@/types/contacts';

export type ContactEmailAddressForm = {
    label: string;
    value: string;
    types: string[];
    isPreferred: boolean;
    group: string;
};

export type ContactPhoneNumberForm = {
    label: string;
    value: string;
    types: string[];
    isPreferred: boolean;
    group: string;
};

export type ContactPostalAddressForm = {
    label: string;
    poBox: string;
    extended: string;
    street: string;
    city: string;
    region: string;
    postalCode: string;
    country: string;
    countryCode: string;
    types: string[];
    isPreferred: boolean;
    group: string;
};

export type ContactDataForm = {
    formattedName: string;
    givenName: string;
    familyName: string;
    organization: string;
    jobTitle: string;
    nickname: string;
    note: string;
    emailAddresses: ContactEmailAddressForm[];
    phoneNumbers: ContactPhoneNumberForm[];
    addresses: ContactPostalAddressForm[];
};

export function emailTypesFor(label: string): string[] {
    if (label === 'home') {
        return ['INTERNET', 'HOME'];
    }

    if (label === 'work') {
        return ['INTERNET', 'WORK'];
    }

    return ['INTERNET'];
}

export function phoneTypesFor(label: string): string[] {
    if (label === 'mobile' || label === 'iPhone') {
        return ['CELL'];
    }

    if (label === 'home') {
        return ['HOME'];
    }

    if (label === 'work') {
        return ['WORK'];
    }

    if (label === 'fax') {
        return ['FAX'];
    }

    return [];
}

export function addressTypesFor(label: string): string[] {
    if (label === 'home') {
        return ['HOME'];
    }

    if (label === 'work') {
        return ['WORK'];
    }

    return [];
}

export function blankEmailAddress(label = 'work'): ContactEmailAddressForm {
    return {
        label,
        value: '',
        types: emailTypesFor(label),
        isPreferred: false,
        group: '',
    };
}

export function blankPhoneNumber(label = 'mobile'): ContactPhoneNumberForm {
    return {
        label,
        value: '',
        types: phoneTypesFor(label),
        isPreferred: false,
        group: '',
    };
}

export function blankPostalAddress(label = 'home'): ContactPostalAddressForm {
    return {
        label,
        poBox: '',
        extended: '',
        street: '',
        city: '',
        region: '',
        postalCode: '',
        country: '',
        countryCode: '',
        types: addressTypesFor(label),
        isPreferred: false,
        group: '',
    };
}

export function emailAddressFromContact(
    email: ContactLabeledValue,
): ContactEmailAddressForm {
    return {
        label: email.label ?? 'work',
        value: email.value,
        types:
            email.types.length > 0
                ? email.types
                : emailTypesFor(email.label ?? 'work'),
        isPreferred: email.isPreferred,
        group: email.group ?? '',
    };
}

export function phoneNumberFromContact(
    phone: ContactLabeledValue,
): ContactPhoneNumberForm {
    return {
        label: phone.label ?? 'mobile',
        value: phone.value,
        types:
            phone.types.length > 0
                ? phone.types
                : phoneTypesFor(phone.label ?? 'mobile'),
        isPreferred: phone.isPreferred,
        group: phone.group ?? '',
    };
}

export function postalAddressFromContact(
    address: ContactPostalAddress,
): ContactPostalAddressForm {
    return {
        label: address.label ?? 'home',
        poBox: address.poBox ?? '',
        extended: address.extended ?? '',
        street: address.street ?? '',
        city: address.city ?? '',
        region: address.region ?? '',
        postalCode: address.postalCode ?? '',
        country: address.country ?? '',
        countryCode: address.countryCode ?? '',
        types:
            address.types.length > 0
                ? address.types
                : addressTypesFor(address.label ?? 'home'),
        isPreferred: address.isPreferred,
        group: address.group ?? '',
    };
}

export function contactDataFromContact(contact: Contact): ContactDataForm {
    const data = contact.data;

    return {
        formattedName: data.formattedName ?? '',
        givenName: data.givenName ?? '',
        familyName: data.familyName ?? '',
        organization: data.organization ?? '',
        jobTitle: data.jobTitle ?? '',
        nickname: data.nickname ?? '',
        note: data.note ?? '',
        emailAddresses:
            data.emailAddresses.length > 0
                ? data.emailAddresses.map(emailAddressFromContact)
                : [blankEmailAddress()],
        phoneNumbers:
            data.phoneNumbers.length > 0
                ? data.phoneNumbers.map(phoneNumberFromContact)
                : [blankPhoneNumber()],
        addresses:
            data.addresses.length > 0
                ? data.addresses.map(postalAddressFromContact)
                : [blankPostalAddress()],
    };
}

export function blankContactData(): ContactDataForm {
    return {
        formattedName: '',
        givenName: '',
        familyName: '',
        organization: '',
        jobTitle: '',
        nickname: '',
        note: '',
        emailAddresses: [blankEmailAddress()],
        phoneNumbers: [blankPhoneNumber()],
        addresses: [blankPostalAddress()],
    };
}

export function contactPrimaryEmail(contact: Contact): string | null {
    const emails = contact.data.emailAddresses;

    return (
        (emails.find((email) => email.isPreferred) ?? emails[0])?.value ?? null
    );
}

export function contactPrimaryPhone(contact: Contact): string | null {
    const phones = contact.data.phoneNumbers;

    return (
        (phones.find((phone) => phone.isPreferred) ?? phones[0])?.value ?? null
    );
}

export function contactDisplayName(contact: Contact): string {
    return (
        contact.data.formattedName ||
        contactPrimaryEmail(contact) ||
        contactPrimaryPhone(contact) ||
        'Unnamed contact'
    );
}

export function contactError(
    errors: Partial<Record<string, string>>,
    key: string,
): string | undefined {
    return errors[key];
}

export function ContactStructuredFields({
    data,
    errors,
    idPrefix,
    setDataField,
}: {
    data: ContactDataForm;
    errors: Partial<Record<string, string>>;
    idPrefix: 'create-contact' | 'edit-contact';
    setDataField: <K extends keyof ContactDataForm>(
        key: K,
        value: ContactDataForm[K],
    ) => void;
}) {
    function updateEmailAddress(
        index: number,
        fields: Partial<ContactEmailAddressForm>,
    ) {
        const emailAddresses = data.emailAddresses.map((email, emailIndex) =>
            emailIndex === index ? { ...email, ...fields } : email,
        );

        setDataField('emailAddresses', emailAddresses);
    }

    function updatePhoneNumber(
        index: number,
        fields: Partial<ContactPhoneNumberForm>,
    ) {
        const phoneNumbers = data.phoneNumbers.map((phone, phoneIndex) =>
            phoneIndex === index ? { ...phone, ...fields } : phone,
        );

        setDataField('phoneNumbers', phoneNumbers);
    }

    function updateAddress(
        index: number,
        fields: Partial<ContactPostalAddressForm>,
    ) {
        const addresses = data.addresses.map((address, addressIndex) =>
            addressIndex === index ? { ...address, ...fields } : address,
        );

        setDataField('addresses', addresses);
    }

    return (
        <>
            <section className="grid gap-2 border-b pb-4 last:border-b-0 last:pb-0">
                <div className="flex items-center justify-between gap-3">
                    <h3 className="text-sm font-medium text-muted-foreground">
                        Email addresses
                    </h3>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-7 px-2 text-xs"
                        onClick={() =>
                            setDataField('emailAddresses', [
                                ...data.emailAddresses,
                                blankEmailAddress(),
                            ])
                        }
                    >
                        <Plus className="size-4" />
                        Add email
                    </Button>
                </div>

                {data.emailAddresses.map((email, index) => {
                    const valueId =
                        index === 0
                            ? idPrefix === 'create-contact'
                                ? 'email'
                                : 'edit-contact-email'
                            : `${idPrefix}-email-${index}`;

                    return (
                        <div
                            key={index}
                            className="grid gap-2 rounded-md bg-muted/20 p-2"
                        >
                            <div className="grid gap-2 sm:grid-cols-[7.5rem_minmax(0,1fr)_2rem]">
                                <div className="grid gap-2">
                                    <Label htmlFor={`${valueId}-label`}>
                                        Type
                                    </Label>
                                    <Select
                                        value={email.label}
                                        onValueChange={(label) =>
                                            updateEmailAddress(index, {
                                                label,
                                                types: emailTypesFor(label),
                                            })
                                        }
                                    >
                                        <SelectTrigger
                                            id={`${valueId}-label`}
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="work">
                                                Work
                                            </SelectItem>
                                            <SelectItem value="home">
                                                Home
                                            </SelectItem>
                                            <SelectItem value="other">
                                                Other
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={valueId}>Email</Label>
                                    <Input
                                        id={valueId}
                                        name={index === 0 ? 'email' : undefined}
                                        type="email"
                                        value={email.value}
                                        onChange={(e) =>
                                            updateEmailAddress(index, {
                                                value: e.target.value,
                                            })
                                        }
                                        placeholder="Email"
                                    />
                                    <InputError
                                        message={contactError(
                                            errors,
                                            `data.emailAddresses.${index}.value`,
                                        )}
                                    />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove email"
                                        disabled={
                                            data.emailAddresses.length === 1
                                        }
                                        onClick={() =>
                                            setDataField(
                                                'emailAddresses',
                                                data.emailAddresses.filter(
                                                    (_, emailIndex) =>
                                                        emailIndex !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </section>

            <section className="grid gap-2 border-b pb-4 last:border-b-0 last:pb-0">
                <div className="flex items-center justify-between gap-3">
                    <h3 className="text-sm font-medium text-muted-foreground">
                        Phone numbers
                    </h3>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-7 px-2 text-xs"
                        onClick={() =>
                            setDataField('phoneNumbers', [
                                ...data.phoneNumbers,
                                blankPhoneNumber(),
                            ])
                        }
                    >
                        <Plus className="size-4" />
                        Add phone
                    </Button>
                </div>

                {data.phoneNumbers.map((phone, index) => {
                    const valueId =
                        index === 0
                            ? idPrefix === 'create-contact'
                                ? 'phone'
                                : 'edit-contact-phone'
                            : `${idPrefix}-phone-${index}`;

                    return (
                        <div
                            key={index}
                            className="grid gap-2 rounded-md bg-muted/20 p-2"
                        >
                            <div className="grid gap-2 sm:grid-cols-[7.5rem_minmax(0,1fr)_2rem]">
                                <div className="grid gap-2">
                                    <Label htmlFor={`${valueId}-label`}>
                                        Type
                                    </Label>
                                    <Select
                                        value={phone.label}
                                        onValueChange={(label) =>
                                            updatePhoneNumber(index, {
                                                label,
                                                types: phoneTypesFor(label),
                                            })
                                        }
                                    >
                                        <SelectTrigger
                                            id={`${valueId}-label`}
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="mobile">
                                                Mobile
                                            </SelectItem>
                                            <SelectItem value="iPhone">
                                                iPhone
                                            </SelectItem>
                                            <SelectItem value="home">
                                                Home
                                            </SelectItem>
                                            <SelectItem value="work">
                                                Work
                                            </SelectItem>
                                            <SelectItem value="fax">
                                                Fax
                                            </SelectItem>
                                            <SelectItem value="other">
                                                Other
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={valueId}>Phone</Label>
                                    <Input
                                        id={valueId}
                                        name={index === 0 ? 'phone' : undefined}
                                        value={phone.value}
                                        onChange={(e) =>
                                            updatePhoneNumber(index, {
                                                value: e.target.value,
                                            })
                                        }
                                        placeholder="Phone"
                                    />
                                    <InputError
                                        message={contactError(
                                            errors,
                                            `data.phoneNumbers.${index}.value`,
                                        )}
                                    />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove phone"
                                        disabled={
                                            data.phoneNumbers.length === 1
                                        }
                                        onClick={() =>
                                            setDataField(
                                                'phoneNumbers',
                                                data.phoneNumbers.filter(
                                                    (_, phoneIndex) =>
                                                        phoneIndex !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </section>

            <section className="grid gap-2 border-b pb-4 last:border-b-0 last:pb-0">
                <div className="flex items-center justify-between gap-3">
                    <h3 className="text-sm font-medium text-muted-foreground">
                        Addresses
                    </h3>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-7 px-2 text-xs"
                        onClick={() =>
                            setDataField('addresses', [
                                ...data.addresses,
                                blankPostalAddress(),
                            ])
                        }
                    >
                        <Plus className="size-4" />
                        Add address
                    </Button>
                </div>

                {data.addresses.map((address, index) => {
                    const streetId = `${idPrefix}-address-${index}-street`;

                    return (
                        <div
                            key={index}
                            className="grid gap-2 rounded-md bg-muted/20 p-2"
                        >
                            <div className="grid gap-2 sm:grid-cols-[7.5rem_minmax(0,1fr)_2rem]">
                                <div className="grid gap-2">
                                    <Label htmlFor={`${streetId}-label`}>
                                        Type
                                    </Label>
                                    <Select
                                        value={address.label}
                                        onValueChange={(label) =>
                                            updateAddress(index, {
                                                label,
                                                types: addressTypesFor(label),
                                            })
                                        }
                                    >
                                        <SelectTrigger
                                            id={`${streetId}-label`}
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="home">
                                                Home
                                            </SelectItem>
                                            <SelectItem value="work">
                                                Work
                                            </SelectItem>
                                            <SelectItem value="other">
                                                Other
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={streetId}>Street</Label>
                                    <Input
                                        id={streetId}
                                        value={address.street}
                                        onChange={(e) =>
                                            updateAddress(index, {
                                                street: e.target.value,
                                            })
                                        }
                                        placeholder="Street"
                                    />
                                    <InputError
                                        message={contactError(
                                            errors,
                                            `data.addresses.${index}.street`,
                                        )}
                                    />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove address"
                                        disabled={data.addresses.length === 1}
                                        onClick={() =>
                                            setDataField(
                                                'addresses',
                                                data.addresses.filter(
                                                    (_, addressIndex) =>
                                                        addressIndex !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>

                            <div className="grid gap-2 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`${idPrefix}-address-${index}-city`}
                                    >
                                        City
                                    </Label>
                                    <Input
                                        id={`${idPrefix}-address-${index}-city`}
                                        value={address.city}
                                        onChange={(e) =>
                                            updateAddress(index, {
                                                city: e.target.value,
                                            })
                                        }
                                        placeholder="City"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`${idPrefix}-address-${index}-region`}
                                    >
                                        State
                                    </Label>
                                    <Input
                                        id={`${idPrefix}-address-${index}-region`}
                                        value={address.region}
                                        onChange={(e) =>
                                            updateAddress(index, {
                                                region: e.target.value,
                                            })
                                        }
                                        placeholder="State"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`${idPrefix}-address-${index}-postal-code`}
                                    >
                                        ZIP
                                    </Label>
                                    <Input
                                        id={`${idPrefix}-address-${index}-postal-code`}
                                        value={address.postalCode}
                                        onChange={(e) =>
                                            updateAddress(index, {
                                                postalCode: e.target.value,
                                            })
                                        }
                                        placeholder="ZIP"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`${idPrefix}-address-${index}-country`}
                                >
                                    Country or region
                                </Label>
                                <Input
                                    id={`${idPrefix}-address-${index}-country`}
                                    value={address.country}
                                    onChange={(e) =>
                                        updateAddress(index, {
                                            country: e.target.value,
                                        })
                                    }
                                    placeholder="Country or region"
                                />
                            </div>
                        </div>
                    );
                })}
            </section>
        </>
    );
}
