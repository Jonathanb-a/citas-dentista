{{-- Interruptor sí/no. El hidden envía "0" cuando el checkbox no está marcado --}}
<label class="flex items-center justify-between gap-4 border border-gray-100 rounded-xl px-4 py-3 cursor-pointer hover:bg-[#FFF6F6] transition-colors duration-200">
    <span class="text-gray-600 text-sm font-medium">{{ $etiqueta }}</span>
    <input type="hidden" name="{{ $campo }}" value="0">
    <input type="checkbox" name="{{ $campo }}" value="1" class="sr-only peer" @checked($marcado)>
    <span class="relative shrink-0 w-11 h-6 bg-gray-200 rounded-full transition-colors duration-200 peer-checked:bg-[#d75078] peer-focus-visible:ring-2 peer-focus-visible:ring-[#fde7ed] after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:bg-white after:rounded-full after:shadow-sm after:transition-transform after:duration-200 peer-checked:after:translate-x-5"></span>
</label>
