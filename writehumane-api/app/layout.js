import './globals.css';

export const metadata = {
  title: 'WriteHumane - AI Content Humanizer API',
  description: 'Make AI-generated content sound naturally human. One API endpoint to pass every AI detector.',
};

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body className="bg-gray-50 text-gray-900 antialiased">
        {children}
      </body>
    </html>
  );
}
