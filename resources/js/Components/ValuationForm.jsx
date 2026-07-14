import { useForm } from '@inertiajs/react';
import { Card, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Label } from './ui/Label';

const propertyTypes = [
    { value: 'einfamilienhaus', label: 'Einfamilienhaus' },
    { value: 'doppelhaushälfte', label: 'Doppelhaushälfte' },
    { value: 'reihenhaus', label: 'Reihenhaus' },
    { value: 'wohnung', label: 'Wohnung' },
    { value: 'maisonette', label: 'Maisonette' },
    { value: 'grundstück', label: 'Grundstück' },
];

export function ValuationForm({ action, onSuccess }) {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        notes: '',
        consultation_requested: false,
        property: {
            property_type: '',
            street: '',
            house_number: '',
            zip: '',
            city: '',
            country: 'DE',
            construction_year: '',
            living_area: '',
            plot_area: '',
            rooms: '',
        },
    });

    const handlePropertyChange = (field, value) => {
        setData('property', { ...data.property, [field]: value });
    };

    const submit = (e) => {
        e.preventDefault();
        post(action, {
            preserveScroll: true,
            onSuccess,
        });
    };

    const inputError = (name) => errors[name] && <p className="mt-1 text-sm text-red-600">{errors[name]}</p>;
    const propertyError = (name) =>
        errors[`property.${name}`] && (
            <p className="mt-1 text-sm text-red-600">{errors[`property.${name}`]}</p>
        );

    return (
        <Card>
            <CardTitle>Jetzt Immobilie bewerten lassen</CardTitle>
            <p className="mb-6 text-sm text-muted">
                Füllen Sie das Formular aus. Sie erhalten direkt eine erste Einschätzung.
            </p>

            <form onSubmit={submit} className="space-y-6">
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <Label htmlFor="first_name">Vorname</Label>
                        <Input
                            id="first_name"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                        />
                        {inputError('first_name')}
                    </div>
                    <div>
                        <Label htmlFor="last_name">Nachname</Label>
                        <Input
                            id="last_name"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                        />
                        {inputError('last_name')}
                    </div>
                    <div>
                        <Label htmlFor="email">E-Mail</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        {inputError('email')}
                    </div>
                    <div>
                        <Label htmlFor="phone">Telefon</Label>
                        <Input
                            id="phone"
                            type="tel"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        {inputError('phone')}
                    </div>
                </div>

                <hr className="border-(--color-border)" />

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <Label htmlFor="property_type">Objekttyp</Label>
                        <select
                            id="property_type"
                            className="input"
                            value={data.property.property_type}
                            onChange={(e) => handlePropertyChange('property_type', e.target.value)}
                        >
                            <option value="">Bitte wählen</option>
                            {propertyTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                        {propertyError('property_type')}
                    </div>

                    <div>
                        <Label htmlFor="street">Straße</Label>
                        <Input
                            id="street"
                            value={data.property.street}
                            onChange={(e) => handlePropertyChange('street', e.target.value)}
                        />
                        {propertyError('street')}
                    </div>
                    <div>
                        <Label htmlFor="house_number">Hausnummer</Label>
                        <Input
                            id="house_number"
                            value={data.property.house_number}
                            onChange={(e) => handlePropertyChange('house_number', e.target.value)}
                        />
                        {propertyError('house_number')}
                    </div>
                    <div>
                        <Label htmlFor="zip">PLZ</Label>
                        <Input
                            id="zip"
                            value={data.property.zip}
                            onChange={(e) => handlePropertyChange('zip', e.target.value)}
                        />
                        {propertyError('zip')}
                    </div>
                    <div>
                        <Label htmlFor="city">Ort</Label>
                        <Input
                            id="city"
                            value={data.property.city}
                            onChange={(e) => handlePropertyChange('city', e.target.value)}
                        />
                        {propertyError('city')}
                    </div>
                    <div>
                        <Label htmlFor="living_area">Wohnfläche (m²)</Label>
                        <Input
                            id="living_area"
                            type="number"
                            min="1"
                            value={data.property.living_area}
                            onChange={(e) => handlePropertyChange('living_area', e.target.value)}
                        />
                        {propertyError('living_area')}
                    </div>
                    <div>
                        <Label htmlFor="plot_area">Grundstücksfläche (m²)</Label>
                        <Input
                            id="plot_area"
                            type="number"
                            min="0"
                            value={data.property.plot_area}
                            onChange={(e) => handlePropertyChange('plot_area', e.target.value)}
                        />
                        {propertyError('plot_area')}
                    </div>
                    <div>
                        <Label htmlFor="construction_year">Baujahr</Label>
                        <Input
                            id="construction_year"
                            type="number"
                            min="1700"
                            value={data.property.construction_year}
                            onChange={(e) => handlePropertyChange('construction_year', e.target.value)}
                        />
                        {propertyError('construction_year')}
                    </div>
                    <div>
                        <Label htmlFor="rooms">Zimmer</Label>
                        <Input
                            id="rooms"
                            type="number"
                            step="0.5"
                            min="1"
                            value={data.property.rooms}
                            onChange={(e) => handlePropertyChange('rooms', e.target.value)}
                        />
                        {propertyError('rooms')}
                    </div>
                </div>

                <div>
                    <Label htmlFor="notes">Notiz / Besonderheiten</Label>
                    <textarea
                        id="notes"
                        rows="3"
                        className="input"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                    />
                    {inputError('notes')}
                </div>

                <div className="flex items-start gap-3">
                    <input
                        id="consultation_requested"
                        type="checkbox"
                        checked={data.consultation_requested}
                        onChange={(e) => setData('consultation_requested', e.target.checked)}
                        className="mt-1 h-5 w-5 rounded border-(--color-border) text-primary focus:ring-primary"
                    />
                    <Label htmlFor="consultation_requested" className="mb-0 cursor-pointer font-normal">
                        Ich möchte eine unverbindliche, kostenlose Erstberatung buchen.
                    </Label>
                </div>

                <Button type="submit" isLoading={processing} className="w-full sm:w-auto">
                    Immobilie bewerten
                </Button>
            </form>
        </Card>
    );
}
