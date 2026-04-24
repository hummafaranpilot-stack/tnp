interface Props {
  platform: string;
}

export default function PlatformBadge({ platform }: Props) {
  const styles: Record<string, string> = {
    BuyGoods: 'bg-blue-100 text-blue-800 border border-blue-200',
    ClickBank: 'bg-gray-100 text-gray-700 border border-gray-200',
  };

  const cls = styles[platform] ?? 'bg-slate-100 text-slate-700 border border-slate-200';

  return (
    <span className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap ${cls}`}>
      {platform}
    </span>
  );
}
