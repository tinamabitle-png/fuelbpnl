{{-- In index.blade.php actions column --}}
<form action="{{ route('admin.users.destroy', $user) }}" 
      method="POST" 
      onsubmit="return confirm('Delete {{ $user->name }}?')"
      class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" 
            class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
            title="Delete User">
        <i class="fas fa-trash"></i>
    </button>
</form>