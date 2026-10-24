export const demoRaces = [
  { id: 1, name: 'Half Marathon', distance_label: '21 KM', category_type: 'individual', is_active: true },
  { id: 2, name: '10 KM Run', distance_label: '10 KM', category_type: 'individual', is_active: true },
  { id: 3, name: '5 KM Run', distance_label: '5 KM', category_type: 'individual', is_active: true },
  { id: 4, name: 'Family Run', distance_label: '2 KM', category_type: 'family', is_active: true },
  { id: 5, name: 'Para Race', distance_label: '2 KM', category_type: 'para', is_active: true },
];

export const demoRegistrations = [
  { id: 1048, reference: 'KFS-1048', first_name: 'Omar', last_name: 'Hassan', email: 'omar.hassan@example.com', phone: '01012345678', race: { name: 'Half Marathon', distance_label: '21 KM' }, kit_type: 'full', shirt_size: 'L', amount: 650, currency: 'EGP', status: 'confirmed', payment_status: 'paid', created_at: '2026-10-24T09:35:00Z' },
  { id: 1047, reference: 'KFS-1047', first_name: 'Mariam', last_name: 'Ali', email: 'mariam.ali@example.com', phone: '01123456789', race: { name: '10 KM Run', distance_label: '10 KM' }, kit_type: 'basic', shirt_size: null, amount: 450, currency: 'EGP', status: 'pending', payment_status: 'pending', created_at: '2026-10-24T08:10:00Z' },
  { id: 1046, reference: 'KFS-1046', first_name: 'Youssef', last_name: 'Mahmoud', email: 'youssef.m@example.com', phone: '01234567890', race: { name: '5 KM Run', distance_label: '5 KM' }, kit_type: 'full', shirt_size: 'M', amount: 600, currency: 'EGP', status: 'confirmed', payment_status: 'paid', created_at: '2026-10-23T18:42:00Z' },
  { id: 1045, reference: 'KFS-1045', first_name: 'Nour', last_name: 'Samir', email: 'nour.samir@example.com', phone: '01098765432', race: { name: 'Family Run', distance_label: '2 KM' }, kit_type: 'basic', shirt_size: null, amount: 350, currency: 'EGP', status: 'pending', payment_status: 'failed', created_at: '2026-10-23T15:20:00Z' },
  { id: 1044, reference: 'KFS-1044', first_name: 'Karim', last_name: 'Adel', email: 'karim.adel@example.com', phone: '01587654321', race: { name: 'Para Race', distance_label: '2 KM' }, kit_type: 'full', shirt_size: 'XL', amount: 500, currency: 'EGP', status: 'confirmed', payment_status: 'paid', created_at: '2026-10-22T11:05:00Z' },
  { id: 1043, reference: 'KFS-1043', first_name: 'Salma', last_name: 'Ibrahim', email: 'salma.ibrahim@example.com', phone: '01099887766', race: { name: 'Half Marathon', distance_label: '21 KM' }, kit_type: 'basic', shirt_size: null, amount: 450, currency: 'EGP', status: 'pending', payment_status: 'pending', created_at: '2026-10-22T08:50:00Z' },
  { id: 1042, reference: 'KFS-1042', first_name: 'Ali', last_name: 'Fathy', email: 'ali.fathy@example.com', phone: '01177889900', race: { name: '10 KM Run', distance_label: '10 KM' }, kit_type: 'full', shirt_size: 'M', amount: 650, currency: 'EGP', status: 'confirmed', payment_status: 'paid', created_at: '2026-10-21T20:15:00Z' },
];

export const demoPayments = demoRegistrations.map((r, index) => ({
  id: 3000 + index,
  reference: `PAY-${r.id}`,
  registration_reference: r.reference,
  runner: `${r.first_name} ${r.last_name}`,
  provider: 'EasyCash',
  amount: r.amount,
  currency: r.currency,
  status: r.payment_status,
  created_at: r.created_at,
  transaction_reference: r.payment_status === 'paid' ? `EC-${76000 + index}` : '—',
}));

export const defaultPrices = [
  { id: '1-full', race_id: 1, race_name: 'Half Marathon', distance_label: '21 KM', kit_type: 'full', price: 650, currency: 'EGP', is_active: true },
  { id: '1-basic', race_id: 1, race_name: 'Half Marathon', distance_label: '21 KM', kit_type: 'basic', price: 450, currency: 'EGP', is_active: true },
  { id: '2-full', race_id: 2, race_name: '10 KM Run', distance_label: '10 KM', kit_type: 'full', price: 650, currency: 'EGP', is_active: true },
  { id: '2-basic', race_id: 2, race_name: '10 KM Run', distance_label: '10 KM', kit_type: 'basic', price: 450, currency: 'EGP', is_active: true },
  { id: '3-full', race_id: 3, race_name: '5 KM Run', distance_label: '5 KM', kit_type: 'full', price: 600, currency: 'EGP', is_active: true },
  { id: '3-basic', race_id: 3, race_name: '5 KM Run', distance_label: '5 KM', kit_type: 'basic', price: 400, currency: 'EGP', is_active: true },
  { id: '4-full', race_id: 4, race_name: 'Family Run', distance_label: '2 KM', kit_type: 'full', price: 500, currency: 'EGP', is_active: true },
  { id: '4-basic', race_id: 4, race_name: 'Family Run', distance_label: '2 KM', kit_type: 'basic', price: 350, currency: 'EGP', is_active: true },
  { id: '5-full', race_id: 5, race_name: 'Para Race', distance_label: '2 KM', kit_type: 'full', price: 500, currency: 'EGP', is_active: true },
  { id: '5-basic', race_id: 5, race_name: 'Para Race', distance_label: '2 KM', kit_type: 'basic', price: 350, currency: 'EGP', is_active: true },
];
