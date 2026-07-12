export default function Modal({ open, onClose, title, children, footer, size='md' }) {
  if (!open) return null;
  const w = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' }[size];
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" onClick={onClose}>
      <div className={`bg-white rounded-lg shadow-xl w-full ${w}`} onClick={(e) => e.stopPropagation()}>
        <div className="px-5 py-4 border-b flex justify-between items-center">
          <h3 className="font-semibold text-slate-900">{title}</h3>
          <button className="text-slate-400 hover:text-slate-700" onClick={onClose}>✕</button>
        </div>
        <div className="p-5">{children}</div>
        {footer && <div className="px-5 py-3 border-t bg-slate-50 rounded-b-lg flex justify-end gap-2">{footer}</div>}
      </div>
    </div>
  );
}
