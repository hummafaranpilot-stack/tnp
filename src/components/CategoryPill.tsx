interface Props {
  category: string;
}

export default function CategoryPill({ category }: Props) {
  const styles: Record<string, string> = {
    'Weight Loss': 'bg-green-100 text-green-800 border border-green-200',
    'Male Enhancement': 'bg-stone-200 text-stone-800 border border-stone-300',
    'Blood Sugar': 'bg-orange-100 text-orange-800 border border-orange-200',
    'Brain Health': 'bg-purple-100 text-purple-800 border border-purple-200',
    'Joint Pain': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
  };

  const cls = styles[category] ?? 'bg-slate-100 text-slate-700 border border-slate-200';

  return (
    <span className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap ${cls}`}>
      {category}
    </span>
  );
}
