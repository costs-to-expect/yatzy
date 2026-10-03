@props(['title', 'rows'])
{{-- What is deleted, or what stays: the content, what it is and where it lives --}}
<section {{ $attributes }}>
    <h2 class="text-sm font-extrabold uppercase tracking-wider text-stone-600">{{ $title }}</h2>
    <div class="mt-2 overflow-x-auto rounded-2xl bg-white shadow-card ring-1 ring-stone-200/70 focus-visible:outline-2 focus-visible:outline-brand-600" role="region" aria-label="{{ $title }}" tabindex="0">
        <table class="w-full min-w-[30rem] text-left text-sm">
            <thead class="bg-stone-50 text-xs uppercase tracking-wider text-stone-600">
                <tr>
                    <th scope="col" class="px-4 py-2.5 font-bold">Content</th>
                    <th scope="col" class="px-4 py-2.5 font-bold">Description</th>
                    <th scope="col" class="px-4 py-2.5 font-bold">Location</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($rows as [$content, $description, $location])
                    <tr>
                        <th scope="row" class="px-4 py-3 font-bold">{{ $content }}</th>
                        <td class="px-4 py-3 text-stone-700">{{ $description }}</td>
                        <td class="px-4 py-3 text-stone-700">{{ $location }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
