<section>
    <header>
        <h2 class="text-lg font-medium text-brand-text-primary">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-brand-text-muted">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" autocomplete="off">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        @if($user->isAdmin())
            <div>
                <x-input-label for="username" :value="__('Usuario (Login)')" />
                <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $user->username)" required autocomplete="off" />
                <x-input-error class="mt-2" :messages="$errors->get('username')" />
            </div>
        @else
            <div>
                <div class="flex items-center justify-between mb-1">
                    <x-input-label for="username" :value="__('Usuario (Login)')" />
                    <span class="text-[10px] font-mono text-[#707B8A] bg-[#11151B] px-2 py-0.5 rounded border border-[#263241] flex items-center gap-1">
                        <span class="material-symbols-outlined text-[13px] text-amber-400">lock</span>
                        <span>No editable</span>
                    </span>
                </div>
                <x-text-input 
                    id="username" 
                    type="text" 
                    class="mt-1 block w-full bg-[#070A0F] text-[#8D98A8] border-[#1F2937] cursor-not-allowed opacity-80 select-none font-mono" 
                    :value="$user->username" 
                    readonly 
                    disabled 
                />
                <p class="mt-1 text-[11px] text-[#707B8A]">
                    El nombre de usuario institucional es permanente y únicamente puede ser gestionado por la Administración del Sistema.
                </p>
            </div>
        @endif

        <div>
            <x-input-label for="email" :value="__('Correo Electrónico (Opcional)')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-brand-text-secondary">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-[#3D82C6] hover:text-[#5B9FE0] rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#164A8A]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-[#20C997]">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[#20C997] font-medium"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
