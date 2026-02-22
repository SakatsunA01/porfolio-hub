export interface Product {
  id: number;
  name: string;
  category: string;
  price: number;
  description: string;
  details: { name: string; description: string }[];
  image: string;
  images: string[];
}

export const products: Product[] = [
  {
    id: 1,
    name: 'Axis Wireless Charger',
    category: 'Carga',
    price: 79.99,
    description: 'Carga tus dispositivos de forma inalambrica con un diseno minimalista y elegante.',
    details: [
      { name: 'Potencia', description: 'Carga rapida de 15W' },
      { name: 'Compatibilidad', description: 'Compatible con todos los dispositivos Qi' },
      { name: 'Material', description: 'Superficie de silicona antideslizante y base de aluminio' },
      { name: 'Indicador', description: 'Indicador LED de estado de carga' },
    ],
    image: '../assets/Axis Wireless Charger/Gemini_Generated_Image_p56tetp56tetp56t.png',
    images: [
      '../assets/Axis Wireless Charger/Gemini_Generated_Image_p56tetp56tetp56t.png',
      '../assets/Axis Wireless Charger/Gemini_Generated_Image_p56teup56teup56t.png',
    ],
  },
  {
    id: 2,
    name: 'Axis Bluetooth Speaker',
    category: 'Audio',
    price: 129.99,
    description: 'Sonido inmersivo y diseno portatil para llevar tu musica a todas partes.',
    details: [
      { name: 'Sonido', description: 'Sonido 360 grados de alta fidelidad' },
      { name: 'Bateria', description: '24 horas de duracion' },
      { name: 'Resistencia', description: 'Resistente al agua IPX7' },
      { name: 'Conectividad', description: 'Bluetooth 5.0' },
    ],
    image: '../assets/Axis Bluetooth Speaker/Gemini_Generated_Image_p56tepp56tepp56t.png',
    images: [
      '../assets/Axis Bluetooth Speaker/Gemini_Generated_Image_p56tepp56tepp56t.png',
      '../assets/Axis Bluetooth Speaker/Gemini_Generated_Image_p56teqp56teqp56t.png',
    ],
  },
  {
    id: 3,
    name: 'Axis Smartwatch',
    category: 'Accesorios',
    price: 249.99,
    description: 'Monitorea tu actividad fisica y recibe notificaciones con estilo.',
    details: [
      { name: 'Pantalla', description: 'AMOLED de 1.4"' },
      { name: 'Sensores', description: 'GPS integrado y sensor de ritmo cardiaco' },
      { name: 'Bateria', description: '7 dias de duracion' },
      { name: 'Material', description: 'Caja de titanio y cristal de zafiro' },
    ],
    image: '../assets/Axis Smartwatch/Gemini_Generated_Image_p56temp56temp56t.png',
    images: [
      '../assets/Axis Smartwatch/Gemini_Generated_Image_p56temp56temp56t.png',
      '../assets/Axis Smartwatch/Gemini_Generated_Image_p56teop56teop56t.png',
    ],
  },
  {
    id: 4,
    name: 'Axis Noise-Cancelling Headphones',
    category: 'Audio',
    price: 349.99,
    description: 'Sumergite en tu musica con cancelacion activa de ruido y sonido cristalino.',
    details: [
      { name: 'Cancelacion de ruido', description: 'Activa hibrida con modo transparencia' },
      { name: 'Audio', description: 'Sonido de alta resolucion con drivers de 40 mm' },
      { name: 'Bateria', description: '30 horas de duracion con una sola carga' },
      { name: 'Diseno', description: 'Ergonomico, ligero y con almohadillas de espuma viscoelastica' },
    ],
    image: '../assets/Axis Noise-Cancelling Headphones/auri1.png',
    images: [
      '../assets/Axis Noise-Cancelling Headphones/auri1.png',
      '../assets/Axis Noise-Cancelling Headphones/Gemini_Generated_Image_1vda0l1vda0l1vda.png',
      '../assets/Axis Noise-Cancelling Headphones/Gemini_Generated_Image_nq8j6knq8j6knq8j.png',
    ],
  },
];
