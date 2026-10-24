// Front-end demo pricing based on the ticket options discussed for KFS Runners.
// Confirm these amounts before launch; the Laravel API should be the source of truth.
export const races = [
  { id: '21k', distance: '21 KM', title: 'Half Marathon', description: 'For runners ready to push their limits.', fullPrice: 650, basicPrice: 450, color: 'red', icon: 'run' },
  { id: '10k', distance: '10 KM', title: '10K Run', description: 'A challenge worth showing up for.', fullPrice: 650, basicPrice: 450, color: 'green', icon: 'run' },
  { id: '5k', distance: '5 KM', title: '5K Run', description: 'Find your pace and enjoy the journey.', fullPrice: 600, basicPrice: 400, color: 'light', icon: 'run' },
  { id: 'family', distance: '2 KM', title: 'Family Run', description: 'Bring your family and run together.', fullPrice: 500, basicPrice: 350, color: 'sand', icon: 'family' },
  { id: 'para', distance: '2 KM', title: 'Para Race', description: 'A race built around access and inclusion.', fullPrice: 500, basicPrice: 350, color: 'sage', icon: 'accessibility' },
];

export const kitOptions = [
  {
    id: 'full', name: 'Full Kit', subtitle: 'The complete race-day experience',
    priceLabel: 'Full kit', items: ['Finisher medal', 'Official KFS Runners T-shirt', 'Runner bag', 'Certificate of participation', 'Race support and photographs'],
  },
  {
    id: 'basic', name: 'Basic Kit', subtitle: 'The essentials for your race',
    priceLabel: 'Basic kit', items: ['Finisher medal', 'Certificate of participation', 'Race support and photographs'],
  },
];

export const money = (value) => new Intl.NumberFormat('en-EG', {
  style: 'currency', currency: 'EGP', maximumFractionDigits: 0,
}).format(value);
