<!-- 'team_member_name' => 'اسم العضو',
        'team_member_etablissment' => 'المؤسسة',
        'team_member_paye' => 'البلد',
        'team_member_grad' => 'المؤهل العلمي',
        'team_member_participation' => 'مساهمة العضو في المشروع', -->
@props(['teamMembers' => []])
    <!-- Button to create a new team member -->
    <div class="flex items-center justify-between w-full">
        @if(count($teamMembers) == 0)
            <h5 class="mb-0 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ __('pages/prix-president/condidature-prix-president/partials.team.no_team_members') }}
            </h5>
         @endif


        <label for="etablissment_1" class="block text-sm font-medium text-gray-700
                                dark:text-gray-200">
            {{ __('pages/prix-president/condidature-prix-president/partials.team_members') }}
            {{ __('pages/prix-president/condidature-prix-president/partials.project_details.si_il_existe') }}
            <button type="button" wire:click="addTeamMember"
                    class="ml-4 px-2 py-1 bg-green-600 text-white rounded
                                hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                <i class="fa fa-plus-circle"></i>
            </button>
        </label>
    </div>

    @foreach($teamMembers as $index => $teamMember)
        <div class="mt-4 p-4 border border-gray-300 rounded-lg
                    bg-white dark:bg-gray-800 dark:border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <h6 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('pages/prix-president/condidature-prix-president/partials.team.team_member') }}
                    {{ $index + 1 }}
                </h6>
                <button type="button" wire:click="removeTeamMember({{ $index }})"
                        class="text-red-600 hover:text-red-800 focus:outline-none">
                    <i class="fa fa-trash"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Team Member Name -->
                <div>
                    <label for="team_member_name_{{ $index }}" class="block text-sm font-medium text-gray-700
                                dark:text-gray-200">
                        {{ __('pages/prix-president/condidature-prix-president/partials.team.team_member_name') }}
                    </label>
                    <input type="text" id="team_member_name_{{ $index }}"
                           wire:model="teamMembers.{{ $index }}.team_member_name"
                           class="mt-1 block w-full border border-gray-300 rounded-md
                                shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                sm:text-sm p-2
                                dark:bg-gray-700 dark:border-gray-600
                                dark:placeholder-gray-400 dark:text-white
                                dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
                </div>

                <!-- Team Member Establishment -->
                <div>
                    <label for="team_member_etablissment_{{ $index }}" class="block text-sm font-medium text-gray-700
                                dark:text-gray-200">
                        {{ __('pages/prix-president/condidature-prix-president/partials.team.team_member_etablissment') }}
                    </label>
                    <input type="text" id="team_member_etablissment_{{ $index }}"
                           wire:model="teamMembers.{{ $index }}.team_member_etablissment"
                           class="mt-1 block w-full border border-gray-300 rounded-md
                                shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                sm:text-sm p-2
                                dark:bg-gray-700 dark:border-gray-600
                                dark:placeholder-gray-400 dark:text-white
                                dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
                </div>
                <!-- Team Member Country -->
                <div>
                    <label for="team_member_paye_{{ $index }}" class="block text-sm font-medium text-gray-700
                                dark:text-gray-200">
                        {{ __('pages/prix-president/condidature-prix-president/partials.team.team_member_paye') }}
                    </label>
                    <input type="text" id="team_member_paye_{{ $index }}"
                           wire:model="teamMembers.{{ $index }}.team_member_paye"
                           class="mt-1 block w-full border border-gray-300 rounded-md
                                shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                sm:text-sm p-2
                                dark:bg-gray-700 dark:border-gray-600
                                dark:placeholder-gray-400 dark:text-white
                                dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
                </div>
                <!-- Team Member Degree -->
                <div>
                    <label for="team_member_grad_{{ $index }}" class="block text-sm font-medium text-gray-700
                                dark:text-gray-200">
                        {{ __('pages/prix-president/condidature-prix-president/partials.team.team_member_grad') }}
                    </label>
                    <input type="text" id="team_member_grad_{{ $index }}"
                           wire:model="teamMembers.{{ $index }}.team_member_grad"
                           class="mt-1 block w-full border border-gray-300 rounded-md
                                shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                sm:text-sm p-2
                                dark:bg-gray-700 dark:border-gray-600
                                dark:placeholder-gray-400 dark:text-white
                                dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
                </div>
                <!-- Team Phone -->
                <div>
                    <label for="team_member_phone_{{ $index }}" class="block text-sm font-medium text-gray-700
                                dark:text-gray-200">
                        {{ __('pages/prix-president/condidature-prix-president/partials.team.team_member_phone') }}
                    </label>
                    <input type="text" id="team_member_phone_{{ $index }}"
                           wire:model="teamMembers.{{ $index }}.team_member_phone"
                           class="mt-1 block w-full border border-gray-300 rounded-md
                                shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                sm:text-sm p-2
                                dark:bg-gray-700 dark:border-gray-600
                                dark:placeholder-gray-400 dark:text-white
                                dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
                </div>
                <!-- Team Email -->
                <div>
                    <label for="team_member_email_{{ $index }}" class="block text-sm font-medium text-gray-700
                                dark:text-gray-200">
                        {{ __('pages/prix-president/condidature-prix-president/partials.team.team_member_email') }}
                    </label>
                    <input type="text" id="team_member_email_{{ $index }}"
                           wire:model="teamMembers.{{ $index }}.team_member_email"
                           class="mt-1 block w-full border border-gray-300 rounded-md
                                shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                sm:text-sm p-2
                                dark:bg-gray-700 dark:border-gray-600
                                dark:placeholder-gray-400 dark:text-white
                                dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
                </div>
                <!-- Team Member Participation -->
                <div class="md:col-span-2">
                    <label for="team_member_participation_{{ $index }}" class="block text-sm font-medium text-gray-700
                                dark:text-gray-200">
                        {{ __('pages/prix-president/condidature-prix-president/partials.team.team_member_participation') }}
                    </label>
                    <input type="number" id="team_member_participation_{{ $index }}"
                              wire:model="teamMembers.{{ $index }}.team_member_participation"
                              rows="4"
                              class="mt-1 block w-full border border-gray-300 rounded-md
                                   shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                   sm:text-sm p-2
                                   dark:bg-gray-700 dark:border-gray-600
                                   dark:placeholder-gray-400 dark:text-white
                                   dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
                </div>
            </div>
        </div>
    @endforeach

